<?php
if(!defined('ROOT_PATH')){header('HTTP/1.0 404 Not Found');die();}

//fileToStr()
//redirects file conversions-to-plaintext to other functions that do the work.
class fileToStr{
	public function __construct(){}
	public function convert($filename,$filepath){
		$ext=pathinfo($filename,PATHINFO_EXTENSION);
		switch($ext){
			case "txt": return file_get_contents($filepath);
			case "html": case "htm": return strip_tags(str_replace(array("<br>","<div>"),"\n",file_get_contents($filepath)));//get rid of all html tags, but keep some linebreaks there.
			case "doc":	return $this->docToText($filepath);
			case "docx": return $this->docxToText($filepath);
			case "odt": return $this->odtToText($filepath);
			case "pdf": return $this->pdfToText($filepath);
			//case "csv"://really awk case. Plus not sanitized. D:
			//DB::query("LOAD DATA INFILE '%0%' INTO TABLE questions FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' IGNORE 1 LINES",($_FILE["file"]["tmp_name"]));
			
			default:
				echo "Unsupported file extension <i>$ext</i> - we currently support txt, html, doc, docx, odt, pdf.";
		}
	}
	
	//DOCX and ODT are variations on zipped XML, but need different methods of adding the linebreaks necessary for the regex to properly read things.
	private function odtToText($filename) {
		return strip_tags(str_replace("</text:p>","</text:p>\n",$this->readZippedXML($filename, "content.xml")));
	}
	private function docxToText($filename) {
		return strip_tags(str_replace(['</w:r></w:p>','</w:r></w:p></w:tc><w:tc>'],[" ","\r\n"],
			$this->readZippedXML($filename,"word/document.xml")));
	}
	
	//http://stackoverflow.com/questions/5540886/extract-text-from-doc-and-docx
	//docToText()
	//Turns a .doc Word file into text. Magically.
	private function docToText($filename) {
		return preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",//Condensed Gourav Mehta.
			implode(" ",array_filter(explode(chr(0x0D),file_get_contents($filename)),function($x){
				return strpos($x,chr(0x00))===FALSE&&strlen($x)!=0;})));
	}
	
	
	//pdf2string with helper ExtractText, extracts string from pdf, from http://php.net/manual/en/ref.pdf.php
	//It just (doesn't) work! Magically, it turns pdf into string.
	//Redacted, since it didn't work.
	//pdfToText()
	//Naturally, this one doesn't work either.
	private function pdfToText($filename){
		//The PDF2Text class is HUGE. Magical black box. See file for citations
		require_once "class.pdf2text.php";
		$a = new PDF2Text();
		$a->setFilename($filename); 
		$a->decodePDF();
		return $a->output(); 
	}

	//The actual zipped-XML function, which works for a number of document formats.
	private function readZippedXML($archiveFile, $dataFile) {
		// Create new ZIP archive
		$zip = new ZipArchive;

		// Open received archive file
		if (true === $zip->open($archiveFile)) {
			// If done, search for the data file in the archive
			if (($index = $zip->locateName($dataFile)) !== false) {
				// If found, read it to the string
				$data = $zip->getFromIndex($index);
				// Close archive file
				$zip->close();
				// Load XML from a string
				// Skip errors and warnings
				$doc = new DOMDocument();
				$doc->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
				
				// Return data without XML formatting tags
				return $doc->saveXML();
			}
			$zip->close();
		}

		// In case of failure return empty string
		return '';
	}

}

?>