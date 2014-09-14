<?php
/* Original author: Gourav Mehta, gouravmehta@gmail.com, +91-9888316141
Modified by Clive Chan (me) to have more concise code and operation.*/
class Doc2Txt{public $text;function __construct($f){if(!isset($f)||!file_exists($f))
return $this->text="File path does not exist.";switch(pathinfo($f,PATHINFO_EXTENSION)){
case "doc":return $this->text = $this->read_doc($f);case "docx":return $this->text = $this->read_docx($f);
default: return $this->text = "Invalid File Type";}}function __toString(){return $this->text;}function
read_doc($f){return preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",
implode(" ",array_filter(explode(chr(0x0D),file_get_contents($f)),function($x){
return strpos($x,chr(0x00))===FALSE&&strlen($x)!=0;})));}function
read_docx($f){/*if(!($zip=zip_open($f))||is_numeric($zip))return false;for($content='';
$zip_entry=zip_read($zip);zip_entry_close($zip_entry))if(zip_entry_open($zip,$zip_entry)!=FALSE&&
zip_entry_name($zip_entry)=="word/document.xml")$content.=zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
zip_close($zip);return strip_tags(str_replace(['</w:r></w:p>','</w:r></w:p></w:tc><w:tc>'], [" ","\r\n"], $content));*/
return strip_tags(str_replace(['</w:r></w:p>','</w:r></w:p></w:tc><w:tc>'],[" ","\r\n"],$this->readZippedXML
($filename,"word/document.xml")));}}/*USAGE: $txt=new Doc2Txt("asdf.doc"); echo $txt; */
?>