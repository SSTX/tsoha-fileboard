<?php

class FileController extends BaseController {
    public static $basePath = '/home/ttiira/htdocs/';
    
    public static function filelist() {
        $files = File::all();
        View::make('file/filelist.html', array('files' => $files));
    }

    public static function viewFile($id) {
        $file = File::find($id);
        View::make('file/viewFile.html', array('file' => $file));
    }

    public static function uploadGet() {
        View::make('file/upload.html');
    }

    public static function uploadPost() {
        $uploadErrors = array();
        $name =  basename($_FILES['fileInput']['name']);
        $path = 'files/' . md5_file($_FILES['fileInput']['tmp_name']);
        $movepath = FileController::$basePath . $path;
        $size = $_FILES['fileInput']['size'];
        $type = mime_content_type($_FILES['fileInput']['tmp_name']);
        $desc = $_POST['fileDescription'];
        $file = new File(array(
            'name' => $name,
            'description' => $desc,
            'size' => $size,
            'path' => $path,
            'type' => $type,
        ));
        if (!isset($FILES['fileInput']['error']) || is_array($FILES['fileInput']['error'])) {
            $uploadErrors[] = 'Invalid parameters';
        } else if (file_exists($movepath)) {
            $uploadErrors[] = 'File already exists';
        } else if ($FILES['fileInput']['error'] == UPLOAD_ERR_NO_FILE) {
            $uploadErrors[] = 'No file selected';
        }
        $validator = $file->validator();
        if ($validator->validate() && empty($uploadErrors)) {
            move_uploaded_file($_FILES['fileInput']['tmp_name'], $movepath);
            chmod($movepath, 0744);
            $file->save();
            Redirect::to('/file/' . $file->id);
        } else {
            $err = array_merge($validator->errors(), $uploadErrors);
            View::make('file/upload.html', array('file' => $file, 'errors' => $err));
        }
        
    }

    public static function editFileGet($id) {
        $file = File::find($id);
        View::make('file/editFile.html', array('file' => $file));
    }
    
    public static function editFilePost($id) {
        $params = $_POST;
        $file = File::find($id);
        $file->name = $params['filename'];
        $file->description = $params['description'];
        $validator = $file->validator();
        if ($validator->validate()) {
            $file->update();
            Redirect::to('/file/' . $file->id);
        } else {
            View::make('file/editFile.html', array('file' => $file, 'errors' => $validator->errors()));
        }
    }
    
    public static function destroyFile($id) {
        $file = File::find($id);
        unlink(FileController::$baseFilePath . $file->name);
        $file->destroy();
        Redirect::to('/filelist');
    }
}
