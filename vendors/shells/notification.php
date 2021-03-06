<?php
class NotificationShell extends Shell {

	var $uses = array('Subscriber', 'Zone');
	
	var $tasks = array('SwiftMailer');
	
	var $swiftTransport = null;
	
	function main() {
		/**
		 * How far ahead of time we should send the notification
		 * This value is in seconds.
		 * 
		 * Since we're using all day events in gCal, 12am - 6h = 6pm the night before
		 */
		$notificationOffset = 60 * 60 * 6;
		
		/**
		 * How far past the notification time we should allow.
		 * In other words, if we're past the notification time, but within this amount,
		 * go ahead and send the notification.
		 * 
		 * This value is in seconds.
		 */
		$gracePeriod = 60 * 60 * 1;
		
		// Current time
		$currentTime = time();
		
		// grab all our zone data
		$zones = $this->Zone->find('all');

		// output current time for log
		$this->out("Current time: " . date('r', $currentTime));
		
		foreach ($zones as $zone) {			
			// get the next scheduled regular pickup for this zone
			$pickup = $this->Zone->get_next_pickup($zone['Zone']['title'], array('type' => 'pickup'));
			
			// when to notify
			$notification_time = $pickup['start_date'] - $notificationOffset;
			
			// Figure out the time difference
			if ($currentTime >= $notification_time) {
				// we're at or past the notification time
				$timeDifference = $currentTime - $notification_time;
				$sendNotification = true;
			} else {
				$sendNotification = false;
			}
			
			// only try to send while at or past the notification time, within the grace period
			if ($sendNotification && $gracePeriod >= $timeDifference) {
				
				$graceStart = $notification_time;
				$graceEnd = $notification_time + $gracePeriod;
				
				$this->out("Grace period starts: " . date('r', $graceStart));
				$this->out("Grace period ends: " . date('r', $graceEnd));
			
				// for each subscriber in the zone
				$subscribers = $this->Subscriber->find('all', array('conditions' => array('Subscriber.zone_id' => $zone['Zone']['id'])));
				
				$this->initializeMailer();
				
				foreach ($subscribers as $subscriber) {
					foreach ($subscriber['Notification'] as $notification) {
						
						if (!empty($notification['last_sent'])) {
							$this->out("Notification last sent: " . date('r', $notification['last_sent']));
						}

						// check to see if we've already sent a notification to this user within the grace period
						if ($notification['last_sent'] == null || !($notification['last_sent'] >= $graceStart && $notification['last_sent'] <= $graceEnd)) {
							$this->out($subscriber['Subscriber']['contact_email'] . " in " . $zone['Zone']['formatted_title'] .
							" about a pickup on " . date('F j Y', $pickup['start_date']));
							
							$subscriberData = array(
								'Subscriber' => $subscriber['Subscriber'],
								'Notification' => $notification,
								'Zone' => $zone['Zone'],
								'Pickup' => $pickup,
								'Provider' => $subscriber['Provider']
							);
							
							if ($this->sendMail($subscriberData)) {
								$this->out("Sent!");
								$this->Subscriber->Notification->id = $notification['id'];
								$this->Subscriber->Notification->saveField('last_sent', time());	
							} else {
								$this->out("Unable to send.");
							}
							
						}
				
					}
				}
			}
		}
	}
	
	function initializeMailer() {
		// SMTP configuration
		if (file_exists(CONFIGS . 'smtp.php')) {
			include(CONFIGS . 'smtp.php');

			// pass SMTP configuration to SwiftMailer component
			foreach (Configure::read('smtp.config') as $key => $value) {
				$this->SwiftMailer->instance->{'smtp' . ucfirst($key)} = $value;
			}
		} else {
			$this->out("Please configure SMTP settings. See config/smtp.default.php.");
		}
		
		// Set transport for later usage
		$this->swiftTransport =& $this->SwiftMailer->instance->init_swiftmail();
	}
	
	function sendMail($subscriberData) {
		$this->SwiftMailer->instance->sendAs = 'text';
		$this->SwiftMailer->instance->from = 'notifications@londontrash.ca';
		$this->SwiftMailer->instance->fromName = 'LondonTrash.ca';
		$this->SwiftMailer->instance->to = $subscriberData['Subscriber']['contact_email'];
		
		// Setting email subject and template for email/SMS
		if ($subscriberData['Provider']['protocol_id'] == 1) {
			// Email
			$this->SwiftMailer->instance->subject = 'LondonTrash.ca reminder';
			$this->SwiftMailer->instance->template = 'pickup';
		} else {
			// SMS
			$this->SwiftMailer->instance->subject = null;
			$this->SwiftMailer->instance->template = 'pickup_sms';
		}

		// pass data to email template
		$this->SwiftMailer->set('subscriberData', $subscriberData);
		
		// logging
		// $this->SwiftMailer->instance->registerPlugin('LoggerPlugin', new Swift_Plugins_Loggers_EchoLogger());

		try { 
			if(!$this->SwiftMailer->instance->fastsend($this->SwiftMailer->instance->template, $this->SwiftMailer->instance->subject, $this->swiftTransport)) {
				foreach($this->SwiftMailer->instance->postErrors as $failed_send_to) {
					$this->log("Failed to send email to: " . $failed_send_to);
					return false;
				} 
			} 
		} 
		catch(Exception $e) { 
			$this->log("Failed to send email: " . $e->getMessage());
			return false;
		}
		return true;
	}

}	 
?>