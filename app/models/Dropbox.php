<?php

class Dropbox extends Eloquent {
    protected $guarded = array();

    public static $rules = array();

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'dropboxes';

    public function entries()
    {
        return $this->hasMany('Entry');
    }
}
