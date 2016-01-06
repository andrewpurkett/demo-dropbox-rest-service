<?php

class Mailbox extends Eloquent {
	protected $guarded = array();

	public static $rules = array();

    protected $table = 'mailboxes';

    public function attachments()
    {
        return $this->hasMany('Attachment');
    }
}
