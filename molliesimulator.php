<?php // phpcs:disable WordPress ?>
<!DOCTYPE html>
<html>
<head>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
	document.addEventListener( 'DOMContentLoaded',
		function() {
			var urlParams = new URLSearchParams( window.location.search );
			var redirect;
			if ( urlParams.has( 'redirect') ) {
				redirect = urlParams.get( 'redirect' );
				alert( 'Wacht op OK voor redirect naar ' + redirect );
				window.location.replace( redirect );
			}
		}
	);
</script>

</head>
<body>
	<h2>Mollie simulatie</h2>
<?php
	$db       = new \SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
	$payments = $db->query( 'SELECT * FROM payments' );
	$refunds  = $db->query( 'SELECT * FROM refunds' );

if ( isset( $_GET['id'] ) ) {
	$url = '';
	$id  = $_GET['id'];
	while ( $row = $payments->fetchArray() ) {
		$betaling = json_decode( $row['data'] );
		if ( $id == $row['id'] ) {
			$url = $betaling->webhookUrl;
		}
	}
	if ( isset( $_GET['refund_status'] ) ) {
		$status = $_GET['refund_status'];
		while ( $row = $refunds->fetchArray() ) {
			if ( $id == $row['id'] ) {
				$refund         = json_decode( $row['data'] );
				$refund->status = $status;
				$db->exec( "UPDATE refunds SET data='" . json_encode( $refund ) . "' WHERE id='$id'" );
				break;
			}
		}
	}
	if ( $url ) {
		echo '<span style="color:green">sending data to webhook</span><br/>';
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "id=$id" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$server_output = curl_exec( $ch );
		curl_close( $ch );
	}
}
?>
<button class="btn btn-primary" type="button" onClick="window.location=window.location.pathname;window.location.Reload();" >refresh</button>
<h2>Payments</h2>
<table class="table table-striped table-hover table-condensed">
	<thead>
		<tr>
			<th scope="col">id</th><th scope="col">order_id</th><th scope="col">description</th><th scope="col">type</th><th scope="col">value</th><td></td>
		</tr>
	</thead>
	<tbody>
	<?php
	while ( $row = $payments->fetchArray() ) :
		$betaling = json_decode( $row['data'], true );
		?>
		<tr>
			<td><?php echo $row['id']; ?></td>
			<td><?php echo $betaling['metadata']['order_id']; ?></td>
			<td><?php echo $betaling['description']; ?></td>
			<td><?php echo $betaling['sequenceType']; ?></td>
			<td><?php echo $betaling['amount']['value']; ?></td>
			<td>
			<?php if ( 'recurring' === $betaling['sequenceType'] ) : ?>
			<a class="btn" href="?id=<?php echo $row['id']; ?>#" >betalen</a>
			<?php else : ?>
			&nbsp;<?php endif ?></td> 
		</tr>
	<?php endwhile ?>
	</tbody>
</table>

<h2>Refunds</h2>
<table class="table table-striped table-hover table-condensed">
	<thead>
		<tr>
			<th scope="col">id</th><th scope="col">order_id</th><th scope="col">description</th><th scope="col">value</th><td></td>
		</tr>
	</thead>
	<tbody>
	<?php
	while ( $row = $refunds->fetchArray() ) :
		$betaling = json_decode( $row['data'], true );
		?>
		<tr>
			<td><?php echo $row['id']; ?></td>
			<td><?php echo $betaling['metadata']['order_id']; ?></td>
			<td><?php echo $betaling['description']; ?></td>
			<td><?php echo $betaling['amount']['value']; ?></td>
			<td><a class="btn btn-success" href="?id=<?php echo $row['id']; ?>&refund_status=refunded#" >terugstorten</a><a class="btn btn-warning" href="?id=<?php echo $row['id']; ?>&refund_status=failed#" >falen</a></td> 
		</tr>
	<?php endwhile ?>
	</tbody>
</table>

<body>
</html>
