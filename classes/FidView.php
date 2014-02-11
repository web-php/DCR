<?php



class FidView{

	private $template = "template_desktop.html";
	private $template_path = "";
	private $basedir;
	private $datadir;
	private $macros = array();

	//----------------------------------------
	public function show(){

		return str_replace(
					array_keys($this->macros),
					array_values($this->macros),
					file_get_contents($this->template_path)
				);
	}

	//----------------------------------------
	public function set_template($template){
		$this->template = $template;
		$this->template_path = $this->basedir . "/template/" . $this->template;
	}
	//----------------------------------------
	public function set_macros($macros){
		$this->macros = $macros;
	}
	//----------------------------------------
	public function __construct(){
		$this->basedir = __DIR__ . "/..";
	}

}

?>