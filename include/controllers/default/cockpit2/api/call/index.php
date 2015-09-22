<?php

class Controller_api_call extends Crunchbutton_Controller_RestAccount {

	public function init() {

		if (!c::admin()->permission()->check(['global', 'support-all', 'support-view', 'support-crud'])) {
			$this->error(401);
		}

		if( c::getPagePiece(2) == 'make-call' ){
			$this->_makeCall();
			exit();
		}

		if( c::getPagePiece(2) == 'register-voip' ){
			$this->_registerVoip();
			exit();
		}

		if( c::getPagePiece(2) == 'send-sms' ){
			$this->_sendSMS();
			exit();
		}

		if( c::getPagePiece(2) == 'send-sms-list' ){
			$this->_sendSMSList();
			exit();
		}

		$call = Call::o(c::getPagePiece(2));

		if (!$call->id_call) {
			$this->error(404);
		}

		switch ($this->method()) {
			case 'get':
				echo $call->json();
				break;

			case 'post':
				// do nothing for now
				break;
		}
	}

	private function _sendSMSList(){
		$message = $this->request()[ 'message' ];
		if( trim( $message ) != '' ){
			$numbers = $this->request()[ 'phone' ];
			foreach( $numbers as $number ){
				if( trim( $number ) != '' ){


					$admin = Crunchbutton_Admin::getByPhone( $number );
					if( $admin->id_admin ){
						$name = $admin->firstName();
					} else {
						$name = '';
					}

					$_message = Crunchbutton_Message_Sms::greeting( $name ) . $message;

					Crunchbutton_Support::createNewWarning(  [ 'dont_open_ticket' => true, 'body' => $_message, 'phone' => $phone ] );

					Crunchbutton_Message_Sms::send([
							'from' => 'driver',
							'to' => $number,
							'message' => $_message,
							'reason' => Crunchbutton_Message_Sms::REASON_SUPPORT
						] );
				}
			}
			echo json_encode( [ 'success' => true ] );
		} else {
			echo json_encode( [ 'error' => 'Error sending text message! Please enter a text!' ] );
		}
	}

	private function _sendSMS(){
		$params = [];
		$params[ 'Action' ] = 'FakeSMS';
		$params[ 'Name' ] = $this->request()[ 'name' ];
		$params[ 'Created_By' ] = c::admin()->firstName();
		$params[ 'Body' ] = $this->request()[ 'message' ];
		$params[ 'From' ] = $this->request()[ 'phone' ];
		if( trim( $params[ 'Body' ] ) != '' && trim( $params[ 'From' ] ) != '' ){
			$support = Crunchbutton_Support::createNewChat( $params );
			if( $support->id_support ){
					echo json_encode( [ 'success' => $support->id_support ] );
			} else {
				echo json_encode( [ 'error' => 'Error creating new chat' ] );
			}
		} else {
			echo json_encode( [ 'error' => 'invalid request' ] );
		}
	}

	private function _registerVoip(){
		if( $this->method() == 'post' ){
			Crunchbutton_Phone_Log::log( $this->request()[ 'phone' ], c::config()->twilio->live->outgoingCustomer, 'call', 'outgoing', 'Voip from cockpit' );
			echo json_encode( [ 'success' => true ] );exit;
		}
	}

	private function _makeCall(){

		if( $this->method() == 'post' ){

			switch ( $this->request()[ 'to' ] ) {
				case 'restaurant':
					$callerId = c::config()->twilio->live->outgoingRestaurant;
					break;
				case 'driver':
					$callerId = c::config()->twilio->live->outgoingDriver;
					break;
				case 'customer':
				default:
					$callerId = c::config()->twilio->live->outgoingCustomer;
					break;
			}

			$num = c::admin()->phone;
			$host = 'beta.crunchr.co';

			$twilio = new Services_Twilio(c::config()->twilio->live->sid, c::config()->twilio->live->token);
			$call = $twilio->account->calls->create(
				$callerId,
				'+1'.$num,
				'http://'.$host.'/api/twilio/outgoing/'.$this->request()[ 'to' ].'?PhoneNumber='.$this->request()[ 'phone' ]
			);

			Crunchbutton_Phone_Log::log( $this->request()[ 'phone' ], $num, 'call', 'outgoing', 'Call from cockpit' );

			Log::debug( [ 'from' => $num, 'to' => $this->request()[ 'phone' ], 'caller' => $this->request()[ 'to' ], 'callerId' => $callerId,  'type' => 'connect-call' ] );

			echo json_encode( [ 'success' => 'Pick up your phone...' ] );exit;
		}

		echo json_encode( [ 'error' => 'Error' ] );exit;

	}

}