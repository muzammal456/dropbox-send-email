<?php
if (isset($_GET['challenge'])) {
	echo $_GET['challenge'];
}else if(isset($_GET)){
	echo "get part";
}else if(isset($_POST)){
	echo "post part";
}else{
	echo "else";
}



?>
