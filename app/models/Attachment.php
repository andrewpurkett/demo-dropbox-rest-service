<?php

class Attachment extends Eloquent {
	protected $guarded = array();

	public static $rules = array();

    public function mailbox()
    {
        return $this->belongsTo('Mailbox');
    }
}
