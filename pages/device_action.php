<?php
	
	//	var_dump( $_GET );
	$action = $_GET[ "action" ];
	$status = FALSE;
	$device = NULL;
	$msg    = NULL;
	if ( $action == "edit" ) {
		$file = fopen( $filename, 'r' );
		while ( ( $line = fgetcsv( $file ) ) !== FALSE ) {
			//$line is an array of the csv elements
			//var_dump( $line );
			if ( $line[ 0 ] == $_GET[ "device_id" ] ) {
				$line[ 1 ] = explode( "|", $line[ 1 ] );
				$device    = $line;
				break;
			}
		}
		fclose( $file );
		
		$status = $Sonoff->getAllStatus( $device[ 2 ] );
		if ( isset( $status->ERROR ) ) {
			$msg = __( "MSG_DEVICE_NOT_FOUND", "DEVICE_ACTIONS" )."<br/>";
			$msg .= $status->ERROR."<br/>";
		}
	} else if ( $action == "delete" ) {
		$device[ 0 ] = $_GET[ "device_id" ];
		$tempfile    = @tempnam( _TMPDIR_, "tmp" ); // produce a temporary file name, in the current directory
		
		if ( !$input = fopen( $filename, 'r' ) ) {
			die( __( "ERROR_CANNOT_READ_CSV_FILE", "DEVICE_ACTIONS", [ "csvFilePath" => $filename ] ) );
		}
		if ( !$output = fopen( $tempfile, 'w' ) ) {
			die( __( "ERROR_CANNOT_CREATE_TMP_FILE", "DEVICE_ACTIONS", [ "tmpFilePath" => $tempfile ] ) );
		}
		
		$idCounter = 1;
		while ( ( $data = fgetcsv( $input ) ) !== FALSE ) {
			if ( $data[ 0 ] == $device[ 0 ] ) {
				continue;
			}
			$data[ 0 ] = $idCounter;
			$idCounter++;
			fputcsv( $output, $data );
		}
		
		fclose( $input );
		fclose( $output );
		
		unlink( $filename );
		rename( $tempfile, $filename );
		
		$msg    = __( "MSG_DEVICE_DELETE_DONE", "DEVICE_ACTIONS" );
		$action = "done";
	}
	
	if ( isset( $_POST ) && !empty( $_POST ) ) {
		
		if ( isset( $_POST[ "search" ] ) ) {
			if ( isset( $_POST[ 'device_ip' ] ) ) {
				$status = $Sonoff->getAllStatus( $_POST[ 'device_ip' ] );
				if ( isset( $status->ERROR ) ) {
					$msg = __( "MSG_DEVICE_NOT_FOUND", "DEVICE_ACTIONS" )."<br/>";
					$msg .= $status->ERROR."<br/>";
				}
			} else {
				$msg = __( "ERROR_PLEASE_ENTER_DEVICE_IP", "DEVICE_ACTIONS" );
			}
		} else if ( !empty( $_POST[ 'device_id' ] ) ) {//update
			$device[ 0 ] = $_POST[ "device_id" ];
			$device[ 1 ] = implode( "|", $_POST[ "device_name" ] );
			$device[ 2 ] = $_POST[ "device_ip" ];
			
			$tempfile = @tempnam( _TMPDIR_, "tmp" ); // produce a temporary file name, in the current directory
			
			
			if ( !$input = fopen( $filename, 'r' ) ) {
				die( __( "ERROR_CANNOT_READ_CSV_FILE", "DEVICE_ACTIONS", [ "csvFilePath" => $filename ] ) );
			}
			if ( !$output = fopen( $tempfile, 'w' ) ) {
				die( __( "ERROR_CANNOT_CREATE_TMP_FILE", "DEVICE_ACTIONS", [ "tmpFilePath" => $tempfile ] ) );
			}
			
			while ( ( $data = fgetcsv( $input ) ) !== FALSE ) {
				if ( $data[ 0 ] == $device[ 0 ] ) {
					$data = $device;
				}
				fputcsv( $output, $data );
			}
			
			fclose( $input );
			fclose( $output );
			
			unlink( $filename );
			rename( $tempfile, $filename );
			
			$msg    = __( "MSG_DEVICE_EDIT_DONE", "DEVICE_ACTIONS" );
			$action = "done";
			
		} else { //add
			
			if ( isset( $_POST[ "search" ] ) ) {
				if ( isset( $_POST[ 'device_ip' ] ) ) {
					$status = $Sonoff->getAllStatus( $_POST[ 'device_ip' ] );
					if ( isset( $status->ERROR ) ) {
						$msg = __( "MSG_DEVICE_NOT_FOUND", "DEVICE_ACTIONS" )."<br/>";
						$msg .= $status->ERROR."<br/>";
					}
				} else {
					$msg = __( "ERROR_PLEASE_ENTER_DEVICE_IP", "DEVICE_ACTIONS" );
				}
			} else {
				$fp          = file( $filename );
				$device[ 0 ] = count( $fp ) + 1;
				$device[ 1 ] = implode( "|", isset( $_POST[ "device_name" ] ) ? $_POST[ "device_name" ] : [] );
				$device[ 2 ] = isset( $_POST[ "device_ip" ] ) ? $_POST[ "device_ip" ] : "";
				
				
				$handle = fopen( $filename, "a" );
				fputcsv( $handle, $device );
				fclose( $handle );
				
				$msg    = __( "MSG_DEVICE_ADD_DONE", "DEVICE_ACTIONS" );
				$action = "done";
			}
			
		}
		
	}

?>

<?php if ( $action == "add" || $action == "edit" ): ?>
	<form class='form'
	      name='save_device'
	      method='post'
	      action='<?php echo _APPROOT_; ?>index.php?page=device_action&action=<?php echo $action ?><?php echo isset( $device )
		      ? "&device_id=".$device[ 0 ] : "" ?>'>
		<input type='hidden' name='device_id' value='<?php echo isset( $device ) ? $device[ 0 ] : ""; ?>'>
		<table class='center-table' border='0' cellspacing='0'>
			<tr>
				<td>IP vom Sonoff:</td>
				<td><input type='text'
				           id="device_ip"
				           name='device_ip'
				           required
				           value='<?php echo( isset( $device ) && !isset( $_POST[ 'device_ip' ] ) ? $device[ 2 ]
					           : ( isset( $_POST[ 'device_ip' ] ) ? $_POST[ 'device_ip' ] : "" ) ); ?>'></td>
				<td>
					<button type='submit'
					        name='search'
					        value='search'
					        class='btn widget'
					>
						<?php echo __( "BTN_SEARCH_DEVICE", "DEVICE_ACTIONS" ); ?>
					</button>
				</td>
			</tr>
			
			
			<?php if ( isset( $status ) && !empty( $status ) && !isset( $status->ERROR ) ): ?>
				<?php if ( isset( $status->WARNING ) && !empty( $status->WARNING ) ): ?>
					<tr>
						<td colspan='3' style='text-align: center; margin-top: 20px; '>
							<p><?php echo __( "MSG_DEVICE_FOUND", "DEVICE_ACTIONS" ); ?></p>
							<p class='error' style='color: red;'><?php echo __( "ERROR" ); ?>
								: <?php echo $status->WARNING; ?></p>
						</td>
					</tr>
				<?php else: ?>
					<tr>
						<td colspan='3' style='text-align: center; margin-top: 20px;'>
							<br/><br/><?php echo __( "MSG_DEVICE_FOUND", "DEVICE_ACTIONS" ); ?><br/><br/>
						</td>
					</tr>
					<?php if ( isset( $status->StatusSTS->POWER ) ): ?>
						<tr>
							<td><?php echo __( "LABEL_NAME", "DEVICE_ACTIONS" ); ?>:</td>
							<td><input type='text'
							           id="device_name"
							           name='device_name[1]'
							           required
							           value='<?php echo isset( $device )
								           ? $device[ 1 ][ 0 ]
								           : ( isset( $_POST[ 'device_name' ][ 1 ] ) ? $_POST[ 'device_name' ][ 1 ]
									           : $status->Status->FriendlyName ); ?>'></td>
							<td class='default-value'>( <a href='#' title='Übernehmen'
							                               class='default-name'><?php echo $status->Status->FriendlyName; ?></a>
							                          )
							</td>
						</tr>
					<?php endif; ?>
					
					
					<?php
					$i     = 1;
					$power = "POWER".$i;
					while ( isset( $status->StatusSTS->$power ) )  : ?>
						<tr>
							<td><?php echo __( "LABEL_NAME", "DEVICE_ACTIONS" ); ?> <?php echo $i; ?>:</td>
							<td><input type='text'
							           id="device_name"
							           name='device_name[<?php echo $i; ?>]'
							           required
							           value='<?php echo isset( $device[ 1 ][ $i - 1 ] )
							                             && !empty(
							           $device[ 1 ][ $i - 1 ]
							           )
								           ? $device[ 1 ][ $i - 1 ]
								           : ( isset( $_POST[ 'device_name' ][ $i ] ) ? $_POST[ 'device_name' ][ $i ]
									           : $status->Status->FriendlyName." ".$i ); ?>'></td>
							<td class='default-value'>( <a href='#'
							                               title='<?php echo __(
								                               "DEVICE_NAME_TOOLTIP",
								                               "DEVICE_ACTIONS"
							                               ); ?>'
							                               class='default-name'><?php echo $status->Status->FriendlyName
							                                                               ." "
							                                                               .$i; ?></a> )
							</td>
						</tr>
						
						
						<?php
						
						$i++;
						$power = "POWER".$i;
						?>
					
					<?php endwhile; ?>
					<tr>
						<td style='text-align: right' colspan='3'>
							<br/><br/>
							<button type='submit'
							        name='submit'
							        value='<?php echo isset( $device ) ? "edit" : "add"; ?>'
							        class='btn widget'
							>
								<?php echo __( "BTN_SAVE", "DEVICE_ACTIONS" ); ?>
							</button>
						</td>
					</tr>
				<?php endif; ?>
			
			<?php elseif ( isset( $status->ERROR ) && $status->ERROR != "" ): ?>
				<div class='center'>
					<p><?php echo __( "MSG_DEVICE_NOT_FOUND", "DEVICE_ACTIONS" ); ?>
						<br/>
						<?php echo $status->ERROR; ?>
					</p>
				
				</div>
			<?php endif; ?>
		</table>
	</form>


<?php elseif ( $action == "done" ): ?>
	<div class='center'>
		<p><?php echo $msg; ?></p>
		<a href='<?php echo _APPROOT_; ?>index.php?page=devices'><?php echo __( "BTN_BACK", "DEVICE_ACTIONS" ); ?></a>
	</div>
<?php endif; ?>

<script>
	$( document ).on( "ready", function () {
		$( ".default-name" ).on( "click", function ( e ) {
			e.preventDefault();
			// console.log( $( this ).parent().parent().find( "input" ) );
			$( this ).parent().parent().find( "input" ).val( $( this ).html() );
		} );
	} );
</script>
