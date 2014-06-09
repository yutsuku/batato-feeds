<?php
class timeLog {
	private $start;
	private $end;
	private $times = array();
	private $activeTime = -1;
	
	public function __construct(){
		$this->start = microtime(true);
	}
	
	public function getTime() {
		return $this->end-$this->start;
	}
	
	public function getTimes() {
		return $this->times;
	}
	
	public function tick() {
		if ( $this->activeTime == -1 ) {
			$this->times[] = microtime(true) - $this->start;
			$this->activeTime = 0;
		} else {
			$this->times[] = microtime(true) - $this->times[$this->activeTime];
			$this->activeTime++;
		}
	}
}
?>