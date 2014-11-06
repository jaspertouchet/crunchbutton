<?php

class Crunchbutton_Admin_Note extends Cana_Table {

	public function lastNoteByAdmin( $id_admin ){
		return Crunchbutton_Admin_Note::q( 'SELECT * FROM admin_note WHERE id_admin = "' . $id_admin . '" ORDER BY id_admin_note DESC LIMIT 1' );
	}

	public function date() {
		if (!isset($this->_date)) {
			$this->_date = new DateTime( $this->date, new DateTimeZone( c::config()->timezone ) );
		}
		return $this->_date;
	}

	public function admin() {
		return Admin::o( $this->id_admin );
	}

	public function exports() {
		$out = [];
		$out[ 'id_admin' ] = $this->id_admin;
		$out[ 'date' ] = $this->date()->format( 'M jS Y g:i:s A' );
		$out[ 'text' ] = $this->text;
		$out[ 'added_by' ] = $this->addedBy()->name;
		return $out;
	}

	public function driver() {
		return $this->admin();
	}

	public function addedBy(){
		return Admin::o( $this->id_admin_added );
	}

	public function updatedBy(){
		return $this->addedBy();
	}

	public function __construct($id = null) {
		parent::__construct();
		$this->table('admin_note')->idVar('id_admin_note')->load($id);
	}
}