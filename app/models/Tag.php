<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of File
 *
 * @author ttiira
 */
class Tag extends BaseModel {

    public $id, $name;

    public function __construct($attributes) {
        parent::__construct($attributes);
    }

    public static function collect($row) {
        return new Tag(array(
            'id' => $row['tag_id'],
            'name' => $row['tag_name']
        ));
    }

    public function validator() {
        $v = Valitron\Validator(get_object_vars($this));
        $v->rule('required', 'name');
        return $v;
    }

    public static function all() {
        $stmt = 'SELECT * FROM tag ORDER BY tag_name ASC';
        $query = DB::connection()->prepare($stmt);
        $query->execute();
        $rows = $query->fetchAll();
        $tags = array();
        foreach ($rows as $row) {
            $tags[] = Tag::collect($row);
        }
        return $tags;
    }

    public static function find($id) {
        $stmt = 'SELECT * FROM tag WHERE tag_id = :id';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array('id' => $id));
        $row = $query->fetch();
        $tag = NULL;
        if ($row) {
            $tag = tag::collect($row);
        }
        return $tag;
    }

    public static function findByName($name) {
        $stmt = 'SELECT * FROM tag WHERE tag_name = :name ORDER BY tag_name ASC';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array('name' => $name));
        $row = $query->fetch();
        if ($row) {
            return Tag::collect($row);
        }
        return null;
    }
    
    public static function linkedTags(File $file) {
        $stmt = 'SELECT tag.* FROM file_metadata,tag,tagged_file '
                . 'WHERE tagged_file.tagged_file = file_metadata.file_id '
                . 'AND tagged_file.tag = tag.tag_id '
                . 'AND file_metadata.file_id = :fileid '
                . 'ORDER BY tag.tag_name ASC';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array('fileid' => $file->id));
        $rows = $query->fetchAll();
        $tags = array();
        foreach ($rows as $row) {
            $tags[] = Tag::collect($row);
        }
        return $tags;
    }
    
    public static function makeLink(File $file, Tag $tag) {
        $stmt = 'INSERT INTO tagged_file (tagged_file, tag) '
                . 'VALUES (:fileid, :tagid)';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array(
            'fileid' => $file->id, 
            'tagid' => $tag->id));
    }
    
    public static function destroyLink(File $file, Tag $tag) {
        $stmt = 'DELETE FROM tagged_file WHERE '
                . 'tagged_file.tagged_file = :fileid '
                . 'AND tagged_file.tag = :tagid';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array(
            'fileid' => $file->id,
            'tagid' => $tag->id
        ));
    }
    
    public function save() {
        $stmt = 'INSERT INTO tag (tag_name) '
            .'VALUES (:name) '
            .'RETURNING tag_id';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array(
            'name' => $this->name,
        ));
        $row = $query->fetch();
        $this->id = $row['tag_id'];
    }

    public function numFilesTagged() {
        $stmt = 'SELECT COUNT (*) AS file_count '
                . 'FROM file_metadata,tagged_file,tag '
                . 'WHERE file_metadata.file_id = tagged_file.tagged_file '
                . 'AND tag.tag_id = tagged_file.tag '
                . 'AND tag.tag_id = :tagid';
        $query = DB::connection()->prepare($stmt);
        $query->execute(array('tagid' => $this->id));
        $row = $query->fetch();
        return $row['file_count'];
    }
    
    public static function removeOrphans() {
        $stmt = 'DELETE FROM tag '
                . 'WHERE tag.tag_id NOT IN '
                . '(SELECT tagged_file.tag '
                . 'FROM tagged_file)';
        $query = DB::connection()->prepare($stmt);
        $query->execute();
    }
}
