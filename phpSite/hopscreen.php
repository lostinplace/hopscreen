<?php
/*** Utility code to get timezone ***/
session_start();
if(!isset($_SESSION['timezone']))
{
    if(!isset($_REQUEST['offset']))
    {
    ?>
    <script>
    var d = new Date()
    var offset= -d.getTimezoneOffset()/60;
    location.href = "<?php echo $_SERVER['PHP_SELF']; ?>?offset="+offset;
    </script>
    <?php    
    }
    else
    {
        $zonelist = array('Kwajalein' => -12.00, 'Pacific/Midway' => -11.00, 'Pacific/Honolulu' => -10.00, 'America/Anchorage' => -9.00, 'America/Los_Angeles' => -8.00, 'America/Denver' => -7.00, 'America/Tegucigalpa' => -6.00, 'America/New_York' => -5.00, 'America/Caracas' => -4.30, 'America/Halifax' => -4.00, 'America/St_Johns' => -3.30, 'America/Argentina/Buenos_Aires' => -3.00, 'America/Sao_Paulo' => -3.00, 'Atlantic/South_Georgia' => -2.00, 'Atlantic/Azores' => -1.00, 'Europe/Dublin' => 0, 'Europe/Belgrade' => 1.00, 'Europe/Minsk' => 2.00, 'Asia/Kuwait' => 3.00, 'Asia/Tehran' => 3.30, 'Asia/Muscat' => 4.00, 'Asia/Yekaterinburg' => 5.00, 'Asia/Kolkata' => 5.30, 'Asia/Katmandu' => 5.45, 'Asia/Dhaka' => 6.00, 'Asia/Rangoon' => 6.30, 'Asia/Krasnoyarsk' => 7.00, 'Asia/Brunei' => 8.00, 'Asia/Seoul' => 9.00, 'Australia/Darwin' => 9.30, 'Australia/Canberra' => 10.00, 'Asia/Magadan' => 11.00, 'Pacific/Fiji' => 12.00, 'Pacific/Tongatapu' => 13.00);
        $index = array_keys($zonelist, $_REQUEST['offset']);
        $_SESSION['timezone'] = $index[0];
    }
}
date_default_timezone_set($_SESSION['timezone']);
?>

<!doctype html>  

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	
	<title>Hop Screen.</title>
	<link rel="stylesheet" href="style.css" type="text/css" />
</head>
<body>
<div id="header"><a href="hopscreen.php"><img src="logo.png" alt="hop screen."></a></div>

<?php
require_once('simple_html_dom.php');

$MIN_BREAK = 60 * 0;
$MAX_BREAK = 60 * 60;

$DAY_START = strtotime("10:00am");
// $DAY_END = strtotime("tomorrow");
$DAY_END = $DAY_START + 60 * 60 * 16;
$DAY_NOW = time();

if(!empty($_GET['movie_list'])) {
	// Write to log file
	if( $fh = fopen("log/requests.log",'a') ) {
	fwrite($fh, $DAY_NOW . "\t" . $_SESSION['location'] . "\t" . $_SESSION['date'] . "\t" . implode("," , $_GET['movie_list'] ) ."\n" );
	fclose($fh);
	}

	print '<h1>Movies</h1>';
	print '<div id="wrap">';
	print '<ul class="film_list">';
    foreach($_GET['movie_list'] as $check) {
            print '<li>'.$check.'</li>';
    }
    
    print '</ul></div><br />';
    
    print '<h1>Schedule</h1>';
    print '<div id="wrap">';
    print '<input type="checkbox" class="toggle-expired"> Dim expired movies<br /><br />';


	/** --- Recursive function for computing film order **/
	function recurse_times( $cur_time , $prev_time , $prev_str , $ctr , $wait_time ) {
		global $PATH_FOUND,$DAY_START,$DAY_END,$DAY_NOW,$MIN_BREAK, $MAX_BREAK, $len_table, $arr_times , $arr_films , $film_ctr;
		
		if ( $cur_time >= count($arr_times) || $ctr == $film_ctr ) return;
		if ( $prev_time != 0 && $arr_times[$cur_time] - $prev_time > $MAX_BREAK ) return;
		
		// See this movie
		foreach( $arr_films[$cur_time] as $movie ) {
			// Check if we've already seen it
			if ( strpos($prev_str,$movie) != FALSE ) continue;
			
			$cur_end = $arr_times[$cur_time] + $len_table[ $movie ];
			
			// --- Format output
			// $new_str = $prev_str . strftime("%I:%M",$arr_times[$cur_time]) . ' - ' . strftime("%I:%M",$cur_end) . ' ' . $movie . ' ';

			// Starting buffer			
			if ( $ctr == 0 ) {
				$new_str = '<table id="film_schedule" width=100%><tr>';
				$new_str .= '<td class="bg" width='.round(95*($arr_times[$cur_time] - $DAY_START)/($DAY_END - $DAY_START),0).'% ></td>';
			} else {
				// Break between films
				$new_str = $prev_str;
				$time_break = $arr_times[$cur_time] - $prev_time;
				$wait_time += $time_break;
				
				if ( round(95*($time_break)/($DAY_END - $DAY_START),0) > 0 ) {
					if ( $prev_time < $DAY_NOW ) $new_str .= '<td class="break expired" '; else $new_str .= '<td class="break" ';
					$new_str .= 'width='.round(95*($time_break)/($DAY_END - $DAY_START),0).'% >';
					if ( $time_break / 60 < 15 ) $new_str .= '</td>'; else $new_str .= $time_break / 60 . '<br /><span class="tiny">min</span></td>';
				}				
			}

			// This film
			if ( $arr_times[$cur_time] < $DAY_NOW ) $new_str .= '<td class="film expired" '; else $new_str .= '<td class="film" ';
			$new_str .= 'width='.round(95*($len_table[ $movie ])/($DAY_END - $DAY_START),0).'% >';
			$new_str .= strftime("%I:%M",$arr_times[$cur_time]) . ' - ' . strftime("%I:%M",$cur_end) . '<br /><b>' . $movie . '</b></td>';

			// Recurse to next film			
			if ( $ctr == $film_ctr - 1 ) {
				// Ending buffer
				if ( $cur_end < $DAY_END ) {
					//$new_str .= '<td class="bg" width='.round(95*($DAY_END - $cur_end)/($DAY_END - $DAY_START),0).'% ></td>';
					$new_str .= '<td class="bg"></td>';
				}
				print $new_str . '<td class="meta" width=5% >'.($wait_time / 60).'<br /><span class="tiny">min</span></td></tr></table>';
				$PATH_FOUND = true;		
			} else {
				// Scan to the next available time
				$next_time = $cur_time + 1;
				while ( $next_time < count($arr_times) && $arr_times[ $next_time ] < $cur_end + $MIN_BREAK ) $next_time++;
				recurse_times( $next_time , $cur_end , $new_str , $ctr + 1 , $wait_time );	
			}
		}
		
		// Try to skip this movie
		recurse_times( $cur_time + 1 , $prev_time , $prev_str , $ctr , $wait_time );
	}
	/* --- End recursive function */
    
    $THEATER_FOUND = FALSE;
    for ( $offset=0 ; $offset < 100 ; $offset+=10 ) {
		/* Fetch movie theaters and corresponding show-times */
		$curl = curl_init(); 
		curl_setopt($curl, CURLOPT_URL, 'http://www.google.com/movies?near="'. str_replace(" " , "%20" , $_SESSION['location']) .'"'.'&start='.$offset.'&date='.$_SESSION['date'] );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
		$str = curl_exec($curl);  
		curl_close($curl);  
		
		$html = str_get_html($str);
		$theater_ctr = 0;
	
		/** --- Parse google results **/	
		foreach($html->find('#movie_results .theater') as $div) {
			$theater_ctr++;
			// print theater and address info
			$theater_str = '<span class="theater_title">'.$div->find('h2 a',0)->innertext."</span>\n";
			$theater_str .= '<span class="theater_info">' . $div->find('.info',0)->innertext . '</span><br />';
			$film_ctr = 0;
	
			$time_table = array();
			$len_table = array();
			
			foreach($div->find('.movie') as $movie) {
				$movie_name = $movie->find('.name a',0)->innertext;
				if ( in_array( $movie_name , $_GET['movie_list'] ) ) {
				
					$theater_str .= '<b>'.$movie_name."</b> \n";
					// *** Compute the movie length
					$info = $movie->find('.info',0)->plaintext;
					$info = explode("-",$info,2);
					$info = str_replace(array('&#8206;','&nbsp'),array(' ',' '),$info[0]);
					$info = trim(preg_replace( '/\s+/', ' ', $info ));
					$len_arr = explode( " " , str_replace(array('hr','min'),array('',''),$info) );
					$len_table[$movie_name] = 60 * (60 * intval($len_arr[0]) + intval($len_arr[1]));
					
					$theater_str .= ' <b>&middot;</b> '.$info.' <b>&middot;</b> ';
					
					// *** Analyze the time fields
					$times = $movie->find('.times',0)->plaintext;
					$times = str_replace(array('&#8206;','&nbsp'),array(' ',' '),$times);
					$times = trim(preg_replace( '/\s+/', ' ', $times ));
					$theater_str .= ' '.$times;
					
					$time_arr = explode(" ",$times);
					$time_type = "pm";
					
					foreach( array_reverse($time_arr) as $time ) {
						$time_suff = substr($time,strlen($time)-2);
						if( $time_suff == "pm" ) {
							$time_type = "pm";
						} else if ( $time_suff == "am" ) {
							$time_type = "am";
						} else {
							$time = $time . $time_type;
						}
						
						$time_table[strtotime($time)][] = $movie_name;
					}
					$theater_str .= "<br />\n";
					$film_ctr++;
				}
			}
			
			if( $film_ctr == count($_GET['movie_list']) ) {
				$THEATER_FOUND = TRUE;
				print $theater_str."<br />\n\n";
				ksort($time_table);
				
				$arr_times = array_keys($time_table);
				$arr_films = array_values($time_table);
				
				$PATH_FOUND = false;
				recurse_times( 0 , 0 , "" , 0 , 0 );
				if ( !$PATH_FOUND ){
					print '<div class="error">&Oslash; Unable to find a viable hop in this theater, try fewer movies.</div>';
				}		
				print '<br /><br />';
			}
		}

		// clean up memory
		$html->clear();		
		if ( $theater_ctr == 0 ) break;
	}
	
	if ( !$THEATER_FOUND ){
		print '<div class="error">Unable to find a theater with all selected movies, try fewer movies.</div>';
	}		
	print '</div><br />';
    
} else if ( !empty($_GET['f_location']) ) {

	$_SESSION['location'] = $_GET["f_location"];
	$_SESSION['date'] = $_GET["f_date"];
	
	/* Construct form for movie selection */
	print '<center>';
	print '<form id="form_movies" action="hopscreen.php" method="get">';
	print '<h1>Movies near '. $_SESSION['location'] .'</h1>';
	print '<div id="wrap">';

	print 'Now, select at most four movies you\'d like to see ';
	if( $_SESSION['date'] == 0 ) print '<b>today</b>'; else if( $_SESSION['date'] == 1 ) print '<b>tomorrow</b>';
	print ': <br /><br />';
	print '<ul class="film_grid">';

	
	/* Fetch list of movies */
	$film_ctr = 0;
	for( $i=0 ; $i<60 ; $i+=10 ) {
	
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, 'http://www.google.com/movies?near="'. str_replace(" " , "%20" , $_SESSION['location']) .'"&sort=1&start='.$i.'&date='.$_SESSION['date']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	curl_close($curl);
	$html = str_get_html($str);
	
	$cur_ctr = 0;
	foreach($html->find('#movie_results .movie') as $div) {
		// print movie title
		print '<li class="film_'.$film_ctr.'"><label for="film_'.$film_ctr.'"><input type="checkbox" class="toggle_film_'.$film_ctr.'" id="film_'.$film_ctr.'" name="movie_list[]" value="'.$div->find('h2 a',0)->innertext.'" />'.$div->find('.header .img',0)->innertext.'<br />'.$div->find('h2 a',0)->innertext.'</label></li>';
		print "\n";
		
		$film_ctr++;
		$cur_ctr++;
	}
		
	$html->clear();
	if ( $cur_ctr == 0 ) break;
	
	}
	print '</ul><br /><input type="image" src="btn_go.png" alt="Submit" />';
	print '</form>';
	print '</div>';
	print '</center>';

} else {
	print '<center>';
	print '<form id="form_location" action="hopscreen.php" method="get">';
	print '<h1></h1>';
	print 'HopScreen tries to find you a theater and schedule for optimal movie-hopping. First, type in your zipcode or location: <br /><br />';
	if( isset($_SESSION['location']) ) {
		print '<input type="text" name="f_location" value="'.$_SESSION['location'].'">';
	} else {
		print '<input type="text" name="f_location">';
	}
	print '<br /><input type="radio" name="f_date" value=0 checked>today';
	print '<input type="radio" name="f_date" value=1>tomorrow<br />';
	print '<br /><input type="image" src="btn_go.png" alt="Submit" /><br />';
	//print '<br /><input type="submit" name="location-submit" value="Submit" /><br />';

	print '</form>';
	print '</center>';
}
	
?>

<div id="footer"><i>Copyright 2013 / Show times powered by Google</i><br /> This service is for legitimate and educational use only, in no way do we condone purchasing a single ticket for multiple movies; smuggling snacks in the lining of one's jacket; or mixing liquor into fruit-juice containers as contraband. <!-- The Megaplex industry has repeatedly demonstrated utmost respect for the customer by bombarding them with unrelated advertisements before the film, offering only the cheapest and most unhealthy concessions, pegging those concession prices to Michelin-star levels, etc. - it is only fair that this show of customer care be reciprocated. All that said, do the right thing and support your local cinema. --> </div>
</html>