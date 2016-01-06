<?php

class Entry extends Eloquent {
    protected $guarded = array();
    protected $hidden = [
        'rev',
    ];

    public static $rules = array();

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'entries';

    public function dropbox()
    {
        return $this->belongsTo('Dropbox');
    }

    public function children()
    {
        return DB::table('entries')->where('original_path', 'LIKE', $this->original_path . '/%')->where('dropbox_id', $this->dropbox_id)->get();
        // TODO: Create a cascade to select by inherited parent_id chain
    }

}
