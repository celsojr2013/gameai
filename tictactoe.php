<?php
echo chr(27).chr(91).'H'.chr(27).chr(91).'J';

//inicializar Board

$brd = new Board(3);


//inicializar player1

$p1 = new Player('X',$brd,'cpu',0.01,0.001);



//inicializer player2

$p2 = new Player('O',$brd,'cpu',0.01,0.001);

//loop de jogo

$train = 100000;

print("Treinando CPU\n");

for($t=1;$t<$train;$t++)
{
	print("{$t} / {$train}\r");
	while (!$brd->checkEnd())
	{
		$p1->play();
		if($brd->checkEnd()) break;
		$p2->play();	
	}

	$p1->end($brd->lastplay);
	$p2->end($brd->lastplay);

	$brd->reset();
}


$p1->debug=true;
$p2->debug=true;
$endgame = false;


$res = readline("Qual Jogador? <X/O> ?:");
if(strtoupper($res) == 'X')
{
	$player1 = new Player('X',$brd,'human',0.05,0.001);

	$player2 = $p2;
} 	
if(strtoupper($res) == 'O')
{
	$player1 = $p1;
	$player2 = new Player('O',$brd,'human',0.05,0.001);
;
} 	


while(!$endgame)
{
	print($brd->pretty($brd->places));
	while (!$brd->checkEnd())
	{
		$player1->play();
		print($brd->pretty($brd->places));
		if($brd->checkEnd()) break;
		$player2->play();	
		print($brd->pretty($brd->places));
	}
	
	echo("Vencedor : ".$brd->checkEnd()."\n");
	$player1->end($brd->lastplay);
	$player2->end($brd->lastplay);

	$brd->reset();


	$res = readline("Jogar novamente? <s/n> ?:"); 	
	echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
	if($res =='n') $endgame=true;
}

class Board
{

	public $size;
	public $plays=0;
	public $lastplay='';
	public $places = [];
	public $states = [];
	public $actState = 0;
	public function  __construct($size)
	{
		$this->size = $size;
		$row = array_fill(0,$size,'   ');
		$this->places = array_fill(0,$size,$row);
		$this->states = array_fill(0,pow(3,$size*$size),array('valueX'=>0.5,'valueO'=>0.5));
		$this->initStatesValues();
	}

	public function reset()
	{
		$this->plays=0;
		$this->lastplay='';
		$this->places=[];
		$row = array_fill(0,$this->size,'   ');
		$this->places = array_fill(0,$this->size,$row);
	}

	public function initStatesValues()
	{
		foreach($this->states as $key=>$state)
		{
			$this->places = $this->stateBoard($key);
			$end = $this->checkEnd();
			if($end == 'X')
			{
				$this->states[$key]['valueX']=1;
				$this->states[$key]['valueO']=-1;
			} elseif($end == 'O')
			{
				$this->states[$key]['valueX']=-1;
				$this->states[$key]['valueO']=1;
			} elseif($end == 'D')
			{
				$this->states[$key]['valueX']=0;
				$this->states[$key]['valueO']=0;
			} 
		}
		$this->places = $this->stateBoard(0);
		
	}

	public function stateBoard($state)
	{
		$l = base_convert($state,10,3);
		$l = str_pad($l, 9, "0", STR_PAD_LEFT);
		$k = -1;
		$row = array_fill(0,$this->size,'   ');
		$places = array_fill(0,$this->size,$row);
		
		for($i=0;$i<$this->size;$i++)
		{
			for($j=0;$j<$this->size;$j++)
			{
				$k++;
				if($l[$k]=='2') $places[$i][$j] = ' X ';
 				if($l[$k]=='1') $places[$i][$j] = ' O ';
			}
		}
		return $places;
	}
	public function boardState($board)
	{
		$i = 0;
		$l = [];
		foreach($board as $row)
		{
			foreach($row as $col)
			{
				$l[] = $col;	
			}
		}
		foreach($l as $key=>$b)
		{
			
			$base = $b == ' X ' ? 2 : $b == ' O ' ? 1 : 0;
			if($b==' X ') $base = 2;
			if($b==' O ') $base = 1;
			if($b=='   ') $base = 0;	
			$p = count($l) - $key - 1;
			$i +=  $base * pow(3,$p);
		}
		
		return $i;
	}

	public function simPut($x,$y,$piece)
	{
		$places = $this->places;

		if($x>$this->size-1 || $y>$this->size-1) return false;
		if($places[$x][$y] == '   ')
		{
			$places[$x][$y] = " {$piece} ";
			$state = $this->boardState($places);
			return $state;
		} else return false;
		
	}
	public function put($x,$y,$piece)
	{

		if($x>$this->size-1 || $y>$this->size-1) return false;
		if($this->places[$x][$y] == '   ')
		{
			$this->places[$x][$y] = " {$piece} ";
			$this->plays++;
			$lastplay=$piece;
			$this->actState = $this->boardState($this->places);
			return true;
		} else return false;
	}

	public function checkEnd()
	{
		$win = $this->checkRows();
		if($win<>'X' and $win<>'O') $win = $this->checkCols();
		if($win<>'X' and $win<>'O') $win = $this->checkDiagonals();
		
		if($this->plays==$this->size*$this->size && ($win!='X' && $win!='O'))
		{
			$win = 'D';
		}

		return $win;
		
	}

	public function checkRows()
	{
		$win=false;
		for($i=0;$i<$this->size;$i++)
		{
			$r = 0;
			for($j=0;$j<$this->size;$j++)
			{
				if($this->places[$i][$j] == ' X ')
				{
					$inc=1;
				} elseif ($this->places[$i][$j] == ' O ')
				{
					$inc=-1;
				} else
				{
					$inc=0;
				}
				$r+=$inc;
			}
			if($r == $this->size )
			{ 
				$win = 'X';
			} elseif ($r == $this->size *-1)
			{
				$win='O';
			}
			if($win=='X' or $win=='O') return $win;
		}
		return false;
	}
	public function checkDiagonals()
	{
		$win=false;
		$r1 = 0;
		$r2 = 0;
		for($i=0;$i<$this->size;$i++)
		{
			if($this->places[$i][$i] == ' X ')
			{
				$inc=1;
			} elseif ($this->places[$i][$i] == ' O ')
			{
				$inc=-1;
			} else
			{
				$inc=0;
			}
			$r1+=$inc;

			if($this->places[$i][$this->size -1 - $i] == ' X ')
			{
				$inc=1;
			} elseif ($this->places[$i][$this->size -1 - $i] == ' O ')
			{
				$inc=-1;
			} else
			{
				$inc=0;
			}
			$r2+=$inc;

		}			

		if($r1 == $this->size || $r2 == $this->size)
		{ 
			$win = 'X';
		} elseif ($r1 == $this->size *-1 || $r2 == $this->size *-1)
		{
			$win='O';
		}
		if($win=='X' or $win=='O') return $win;
		
		return false;
	}

	public function checkCols()
	{
		$win=false;
		for($i=0;$i<$this->size;$i++)
		{
			$r = 0;
			for($j=0;$j<$this->size;$j++)
			{
				if($this->places[$j][$i] == ' X ')
				{
					$inc=1;
				} elseif ($this->places[$j][$i] == ' O ')
				{
					$inc=-1;
				} else
				{
					$inc=0;
				}
				$r+=$inc;
			}
			if($r == $this->size )
			{ 
				$win = 'X';
			} elseif ($r == $this->size *-1)
			{
				$win='O';
			}
			if($win=='X' or $win=='O') return $win;
		}
		return false;
	}

	public function pretty($board)
	{
		foreach($board as $row)
		{
			$row = implode("|",$row);
			$rows[] = $row;
		}
		$rows = implode("\n",$rows);
		return("\n".$rows."\n");
				
	} 

}

class Player
{
	public $history = [];

	public $piece = '';

	public $board = null;

	public $epsilon = 0.05;

	public $learningRate = 0.01;

	public $controller = '';

	public $debug = false;

	public $V = [];

	public function __construct($piece,$board,$controller,$epsilon,$learningRate)
	{
		$this->board = $board;
		$this->piece = $piece;
		$this->controller=$controller;
		$this->epsilon = $epsilon;
		$this->learninRage = $learningRate;

		foreach($board->states as $state)
		{
			$this->V[] = $state['value'.$this->piece];
			//$this->V[] = (mt_rand() / mt_getrandmax()) * 1;			
		}
	}

	public function play()
	{
		if($this->controller=='human')
		{
			$notplay=true;
			while($notplay)
			{
				$pos = readline("jogador {$this->piece}, Escolha uma posicao:"); 	
				$pos = explode(',',$pos);
				$y = intval($pos[0]);
				$x = intval($pos[1]);
				$put = $this->board->put($x,$y,$this->piece);
				if(!$put) {
					echo("\nJogada Inválida\n");
					$notplay = true;
				} else {
					$notplay = false;
				}
				
			}
		}
		if($this->controller=='cpu')
		{

			$r = mt_rand() / mt_getrandmax();
			if($r <= $this->epsilon)
			{
				$x = rand(0,$this->board->size-1);
				$y = rand(0,$this->board->size-1);
				while(!$this->board->put($x,$y,$this->piece))
				{
					$x = rand(0,$this->board->size);
                        		$y = rand(0,$this->board->size);
				}
			} else {
				$possibleRewards = [];
				$pos= [];
				for($i=0;$i<$this->board->size;$i++)
				{
					for($j=0;$j<$this->board->size;$j++)
					{
						$sim = $this->board->simput($i,$j,$this->piece);
						if($sim==true)
						{
							 $possibleRewards[$sim]  = $this->V[$sim];
							 $pos[$sim] = array($i,$j);
						}
					}
				}
				if($this->debug) $this->printPlay($possibleRewards,$pos);
				$max = array_search(max($possibleRewards),$possibleRewards);

				$this->board->put($pos[$max][0],$pos[$max][1],$this->piece);
			}
			$this->history[] = $this->board->boardState($this->board->places);
		}
		
	}

	public function end($winner)
	{
		if($winner<>$this->piece)
		{
			$this->history[] = $this->board->boardState($this->board->places);
		}
		$invhistory = array_reverse($this->history);
		$reward = $this->board->states[$this->board->boardState($this->board->places)]['value'.$this->piece];
	
		
		$target = $reward;
		
		foreach($invhistory as $h)
		{
			$value = $this->V[$h] + $this->learningRate*($target - $this->V[$h]);
			$this->V[$h] = $value;
			$target = $value;
		}
		$this->history = [];

	}

	public function printPlay($r,$p)
	{
		$brd = $this->board->places;
		foreach($brd as $i=>$row)
		{	foreach($row as $j=>$col)
			{
				$re = false;
				foreach($p as $k=>$ps)
				{

					if($ps[0]==$i && $ps[1]==$j) 
					{
						$brd[$i][$j] = str_pad(round($r[$k],4),5,' ',STR_PAD_BOTH);
						$re = true;
					}
				}
				if(!$re) $brd[$i][$j] = '-----';
			}
		}
		echo($this->board->pretty($brd));
		
	}

}
?>
