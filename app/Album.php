<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class Album extends Model
{
    public function photos()
    {
        return $this->hasMany('App\Photo','album_id','id');
    }


    /**
     * Rurns album-attributes into a front-end friendly format. Note that some attributes remain unchanged.
     * @return array Returns album-attributes in a normalized structure.
     */
    public function prepareData() {

        // This function requires the following album-attributes and turns them
        // into a front-end friendly format: id, title, public, sysstamp, password
        // Note that some attributes remain unchanged

        // Init
        $album = array();

        // Set unchanged attributes
        $album['id']     = $this->id;
        $album['title']  = $this->title;
        $album['public'] = strval($this->public);
        $album['hidden'] = strval($this->visible_hidden);

//        $album['owned'] =

        // Additional attributes
        // Only part of $album when available
        $album['description'] = strval($this->description);
        $album['visible'] = strval($this->visible_hidden);
        $album['downloadable'] = strval($this->downloadable);

        // Parse date
        $album['sysdate'] = $this->created_at->format('F Y');
        $album['min_takestamp'] = $this->min_takestamp == 0 ? '' : strftime('%B %Y', $this->min_takestamp);
        $album['max_takestamp'] = $this->max_takestamp == 0 ? '' : strftime('%B %Y', $this->max_takestamp);

        // Parse password
        $album['password'] = ($this->password == '' ? '0' : '1');

        // Parse thumbs or set default value
//        $album['thumbs'] = explode(',', $this->thumbs);
//        $album['types'] = (isset($this->types) ? explode(',', $this->types) : array());

        return $album;
    }



    public function gen_thumbs($return)
    {

        $return['thumbs'] = array();
        $return['types'] = array();

        $thumbs_types = Photo::select('thumbUrl', 'type')
            ->where('album_id','=',$this->id)
            ->orderBy('star','DESC')
            ->orderBy(Configs::get_value('sortingPhotos_col'),Configs::get_value('sortingPhotos_order'))
            ->limit(3)->get();

        // For each thumb
        $k = 0;
        foreach ($thumbs_types as $thumb_types) {
            $return['thumbs'][$k] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_THUMB') . $thumb_types->thumbUrl;
            $return['types'][$k] = Config::get('defines.urls.LYCHEE_URL_UPLOADS_THUMB') . $thumb_types->type;
            $k++;
        }

        return $return;
    }


    public function get_albums()
    {
        $return = array();
        $i = 0;
        foreach($this->children as $album)
        {
            $album_t = $album->prepareData();
            $album_t = $album->gen_thumbs($album_t);
            $return[$i] = $album_t;
            $i++;
        }
        return $return;
    }

    /**
     * @param string $password
     * @return boolean Returns when album is public.
     */
    public function checkPassword(string $password) {

        // Check if password is correct
        return ($this->password == '' || Hash::check($password, $this->password));
//        if ($this->password == '') return true;
//        if ($this->password === crypt($password, $this->password)) return true;
//        return false;

    }

    public function update_min_max_takestamp() {
        $min = Photo::where('album_id','=',$this->id)->min('takestamp');
        $max = Photo::where('album_id','=',$this->id)->max('takestamp');
        $this->min_takestamp = $min; //min($this->min_takestamp, $min);
        $this->max_takestamp = $max; //max($this->max_takestamp, $max);
    }

    static public function reset_takestamp() {
        $albums = Album::all();
        foreach($albums as $album)
        {
            $album->update_min_max_takestamp();
            $album->save();
        }
    }

    public function owner() {
        return $this->belongsTo('App\User','owner_id','id');
    }


    public static function get_albums_user($id) {
        return Album::where('owner_id', '<>', $id)
            ->where('parent_id','=',null)
            ->Where(
                function ($query) use ($id) {
                    $query->whereIn('id', function ($query) use ($id)
                    {
                        $query->select('album_id')
                            ->from('user_album')
                            ->where('user_id','=',$id);
                    })
                    ->orWhere(
                        function ($query) {
                            $query->where('public','=',true)->where('visible_hidden','=',true);
                        });
                })
            ->orderBy(Configs::get_value('sortingAlbums_col'),Configs::get_value('sortingAlbums_order'))
            ->get();
    }


    public static function merge(array $albums1, array $albums2) {
        $return = $albums1;

        foreach ($albums2 as $album2_t)
        {
            $found = false;
            foreach ($albums1 as $album1_t)
            {
                if ($album1_t->id == $album2_t->id)
                {
                    $found = true;
                    break;
                }
            }

            if(!$found)
            {
                $return[] = $album2_t;
            }
        }

        return $return;
    }

    public function children() {
        return $this->hasMany('App\Album','parent_id','id');
    }

    public function parent() {
        return $this->belongsTo('App\Album','id','parent_id');
    }
}
