<?php

class DropboxApi{

	public $email = 's.subtain@gmail.com';
	public $emailSubject = 'File Upload in Dropbox';
	public $headers = [
						'Authorization: Bearer _3iD-4GH8cAAAAAAAAAAbtgidy5EmmZYGmT4BFttAE3w0wCT8_WAAro-QiCrxwho',
						'Content-Type: application/json'
					];
	 
	public function filePath(){
		return $_SERVER['DOCUMENT_ROOT'] . "/dropbox-send-email/cursor_file.txt";
	}
	public function getLatestCursor(){

		$fields = '{
			"path": "",
			"recursive": true,
			"include_media_info": false,
			"include_deleted": false,
			"include_has_explicit_shared_members": false,
			"include_mounted_folders": true,
			"include_non_downloadable_files": true
		}';

		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://api.dropboxapi.com/2/files/list_folder/get_latest_cursor' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $this->headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS,  $fields  );
		$result 		= curl_exec($ch );
		$get_result 	= json_decode($result);
		curl_close( $ch );

		$get_cursor = $get_result->cursor;
		return $get_cursor;
	}
	public function latestUploadedFile($cursor){

		$give_cursor_to_list_continue = '{
			"cursor": "'.$cursor.'"
		}'; 
 
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://api.dropboxapi.com/2/files/list_folder/continue' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $this->headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS,  $give_cursor_to_list_continue  );
		$list_continue_result = curl_exec($ch );
		$get_continue_list = json_decode($list_continue_result);
		$get_continue_cursor = $get_continue_list->cursor;
		curl_close( $ch );
		$fp = fopen($this->filePath() ,"wb");
		fwrite($fp , $get_continue_cursor);
		fclose($fp);
		$file_list = $get_continue_list->entries; 
		return $file_list;

	}
	public function saveResult($result){
		$file_name = $result[0]->name;
		$folder_path = dirname($result[0]->path_display);
		if($folder_path == '/'){
			$file_url =  $folder_path.$file_name;
			$get_shareable_link = $this->getShareAbleLink($file_url);
		}else{
			$get_shareable_link = $this->getShareAbleLink($folder_path);
		} 
	 
		$content = $file_name.' has been uploaded'."\n";
		$content .= "File is uploaded on this path. "."'.$folder_path.'"."\n";
		$content .= "ShareAble Link: ". $get_shareable_link."\n";
		// Path where result file is created and save result in it.
		$fp = fopen($_SERVER['DOCUMENT_ROOT'] . "/dropbox-send-email/save_result.txt" ,"wb");
		fwrite($fp , $content);
		fclose($fp);
 
		if($fp){
			return [$file_name, $get_shareable_link];
		}
		return false;

	}
	public function getShareAbleLink($url){
		$path = '{
			"path": "'.$url.'",
			"short_url": false
		}'; 
 
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://api.dropboxapi.com/2/sharing/create_shared_link');
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $this->headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS,  $path  );
		$link_data 	= curl_exec($ch );
		$get_link 	= json_decode($link_data);
		$link = $get_link->url;
		curl_close( $ch );
		 
		return $link;
	}
	public function getUploadedFile(){
 
		if (isset($_GET['challenge'])) {
			echo $_GET['challenge'];
			//return $_GET['challenge'];
		}elseif($_SERVER['REQUEST_METHOD'] == 'POST'){

			if(file_exists($this->filePath())){
				$get_cursor = file_get_contents($this->filePath());
			}else{
				$get_cursor = $this->getLatestCursor();
			}

			$get_uploaded_file = $this->latestUploadedFile($get_cursor);
			if($get_uploaded_file){
				$save_result = $this->saveResult($get_uploaded_file); 
				if($save_result){
					return $save_result;
				}else{
					return false;
				}
			}else{
				return false;
			} 
		}
	}
}
 
$dropbox_api = new DropboxApi();
$latest_file = $dropbox_api->getUploadedFile();
if($latest_file != false && count($latest_file) == 2){
$linkURL= $latest_file[1]; 
$folderName = $latest_file[0];

}
//$linkURL=
//$folderName
print_r($latest_file);
 
exit;

?>
