<?php 
class Exception 
{
  public $Message;
  public function __construct($msg='')
  {
    $this->Message = $msg;
  }
	public function getMessage()
	{
		return $this->Message;
	}
}
?>