<?php
/**
 * Mollie simulator.
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.0
 *
 * @package    Kleistad
 */

// phpcs:disable WordPress

$melding = '';
$db      = new SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
$db->busyTimeout( 5000 );

if ( isset( $_GET[ 'idealupdate'] ) ) {
	$id  = $_GET[ 'id' ];
	$res = $db->query( "SELECT * FROM payments WHERE id='$id'" );
	$row = $res->fetchArray();
	if ( false !== $row ) {
		$payment         = json_decode( $row['data'] );
		$payment->status = $_GET[ 'status' ];
		$db->exec( "UPDATE payments set data='" . /** @scrutinizer ignore-type */ json_encode( $payment ) . "' WHERE id='$id'" ); //phpcs:ignore
		if ( 'first' === $payment->sequenceType && 'paid' === $payment->status ) {
			$res = $db->query( "SELECT data FROM mandates WHERE id='{$payment->mandateId}'" );
			$row = $res->fetchArray();
			if ( false !== $row ) {
				$mandate                = json_decode( $row['data'] );
				$mandate->status        = 'valid';
				$mandate->signatureDate = date( 'c' );
				$db->exec( "UPDATE mandates set data='" . /** @scrutinizer ignore-type */ json_encode( $mandate ) . "' WHERE id='{$payment->mandateId}'" ); //phpcs:ignore
			} else {
				$melding = fout( 'Mandate niet gevonden' );
			}
		}
		if ( empty( $melding ) && feedback( $id, $payment->webhookUrl ) ) {
			header( 'location: ' . $payment->redirectUrl );
			die();
		} else {
			$melding = fout( 'Geen output channel beschikbaar' );
		}
	} else {
		$melding = fout( 'Payment niet gevonden' );
	}
}

?>
<!DOCTYPE html>
<html lang="nl">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Mollie simulator</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
		integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" >
	<!-- jQuery library -->
	<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
		integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous">
	</script>
	<script>
		function home() {
			var url = window.location.protocol + '//' + window.location.hostname + window.location.pathname;
			window.location.replace( url );
		}

		$( document ).ready(
			function() {
				$( '.table' ).DataTable(
					{
						'lengthMenu'  : [ 5, 10, 25 ],
						'page-length' : 5
					}
				);
			}
		);
	</script>
	<style>
		.table-condensed{
			 font-size: 12px;
		}
	</style>
</head>

<body>
	<div class="container">
		<div class="row">
			<div class="col">
			<h2>Mollie simulatie</h2>
			</div>
			<div class="col-sm-10">
				<?php
				echo $melding;
				if ( isset( $_GET[ 'refundstatus' ] ) ) {
					/**
					 * Verwerk een refund
					 */
					echo verwerk_refund( $_GET[ 'id' ] );
				} elseif ( isset( $_GET[ 'incassostatus' ] ) ) {
					/**
					 * Toon openstaande incasso's en refunds
					 */
					echo verwerk_incasso( $_GET[ 'id'] );
				} elseif ( isset( $_GET[ 'id' ] ) ) {
					/**
					 * Aangeroepen vanuit betaalformulier
					 */
					echo betaalformulier( $_GET[ 'id' ] );
				} else {
					/**
					 * Toon openstaande incasso's en refunds
					 */
					echo toon_openstaand();
				}
				$db->close();
				unset( $db );
				?>
			</div>
			<div class="col">
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
		integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
	</script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
		integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
	</script>
	<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" >
	</script>
	<script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js" >
	</script>
</body>

</html>

<?php

/**
 * Toon een success tekst
 *
 * @param string $tekst De notificatie.
 */
function succes( $tekst ) {
	return "<div class=\"row alert alert-success\" >$tekst</div>";
}

/**
 * Toon een fout tekst
 *
 * @param string $tekst De notificatie.
 */
function fout( $tekst ) {
	return "<div class=\"row alert alert-danger\" >$tekst</div>";
}

/**
 * Aanroep van de callback service van de website
 *
 * @param string $id  Het payment id.
 * @param string $url De webhook url.
 */
function feedback( $id, $url ) {
	$ch = curl_init();
	if ( false !== $ch ) {
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "id=$id" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_exec( $ch );
		curl_close( $ch );
	}
	return $ch;
}

/**
 * Toon het formulier
 *
 * @param string $id  Het payment id.
 */
function betaalformulier( $id ) {
	global $db;
	$res = $db->query( "SELECT data FROM payments WHERE id='$id'" );
	$row = $res->fetchArray();
	if ( false === $row ) {
		return fout( 'Payment niet gevonden' );
	}
	$payment = json_decode( $row['data'] );
	ob_start();
	?>
<form action="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]"; ?>" method="GET">
	<input type="hidden" name="id" value="<?php echo $id; ?>">
	<input type="hidden" name="idealupdate" >
	<div class="form-group row">
		<label for="description" class="col-sm-2 col-form-label">beschrijving</label>
		<div class="col-sm-6">
			<input class="form-control" id="description" value="<?php echo $payment->description; ?>" disabled>
		</div>
	</div>
	<div class="form-group row">
		<label for="bedrag" class="col-sm-2 col-form-label">bedrag</label>
		<div class="col-sm-6">
			<input class="form-control" id="bedrag" value="<?php echo $payment->amount->value; ?>" disabled>
		</div>
	</div>
	<fieldset class="form-group">
		<div class="row">
			<legend class="col-form-label col-sm-2 pt-0">betaal status</legend>
			<div class="col-sm-6">
				<div class="form-check">
					<input type="radio" id="paid" class="form-check-input" name="status" value="paid" required>
					<label for="paid" class="form-check-label">Betaald</label>
				</div>
				<div class="form-check">
					<input type="radio" id="failed" class="form-check-input" name="status" value="failed">
					<label for="failed" class="form-check-label">Mislukt</label>
				</div>
				<div class="form-check">
					<input type="radio" id="canceled" class="form-check-input" name="status" value="canceled">
					<label for="canceled" class="form-check-label">Geannuleerd</label>
				</div>
				<div class="form-check">
					<input type="radio" id="expired" class="form-check-input" name="status" value="expired">
					<label for="expired" class="form-check-label">Verlopen</label>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="form-group row">
		<div class="col-sm-6">
			<button type="submit" class="btn btn-primary" name="idealstatus">Verder...</button>
		</div>
	</div>
</form>
	<?php
		return ob_get_clean();
}

/**
 * Wijzig de refundstatus en rapporteer deze aan de website
 *
 * @param string $id  Het payment id.
 */
function verwerk_refund( $id ) {
	global $db;
	$res = $db->query( "SELECT payment_id, data FROM refunds WHERE id='$id'" );
	$row = $res->fetchArray();
	if ( false !== $row ) {
		$refund         = json_decode( $row['data'] );
		$refund->status = $_GET['refundstatus'];
		$payment_id     = $row['payment_id'];
		$db->exec( "UPDATE refunds set data='" . /** @scrutinizer ignore-type */ json_encode( $refund ) . "' WHERE id='$id'" );	 //phpcs:ignore
		$res = $db->query( "SELECT data FROM payments WHERE id='$payment_id'" );
		$row = $res->fetchArray();
		if ( false !== $row ) {
			$payment = json_decode( $row['data'] );
			$html    = feedback( $payment_id, $payment->webhookUrl ) ? succes( 'Verzenden data naar website' ) : fout( 'Geen output channel beschikbaar' );
		} else {
			$html = fout( 'Payment niet gevonden' );
		}
	} else {
		$html = fout( 'Refund niet gevonden' );
	}
	return $html . '<div class="row"><button class="btn btn-primary" type="button" onClick="home();">Verder</button></div>';
}

/**
 * Wijzig de incassostatus en rapporteer deze aan de website
 *
 * @param string $id  Het payment id.
 */
function verwerk_incasso( $id ) {
	global $db;
	$res = $db->query( "SELECT data FROM payments WHERE id='$id'" );
	$row = $res->fetchArray();
	if ( false !== $row ) {
		$incasso         = json_decode( $row['data'] );
		$incasso->status = $_GET['incassostatus'];
		$db->exec( "UPDATE payments set data='" . /** @scrutinizer ignore-type */ json_encode( $incasso ) . "' WHERE id='$id'" );	 //phpcs:ignore
		$html = feedback( $id, $incasso->webhookUrl ) ? succes( 'Verzenden data naar website' ) : fout( 'Geen output channel beschikbaar' );
	} else {
		$html = fout( 'Payment niet gevonden' );
	}
	return $html . '<div class="row"><button class="btn btn-primary" type="button" onClick="home();">Verder</button></div>';
}

/**
 * Toon de openstaande incasso's en refund's
 */
function toon_openstaand() {
	global $db;
	$payments = $db->query( 'SELECT * FROM payments' );
	$refunds  = $db->query( 'SELECT * FROM refunds' );
	ob_start();
	?>
<button class="btn btn-primary" type="button"
	onClick="home();">refresh</button>
<h3>Payments</h3>
<table class="table table-striped table-hover table-condensed" data-order='[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th scope="col">id</th>
			<th scope="col">order_id</th>
			<th scope="col">description</th>
			<th scope="col">type</th>
			<th scope="col">value</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php
		while ( $row = $payments->fetchArray() ) {
			$betaling = json_decode( $row['data'] );
			if ( 'oneoff' !== $betaling->sequenceType ) {
				?>
		<tr>
			<td><?php echo $row['id']; ?></td>
			<td><?php echo $betaling->metadata->order_id; ?></td>
			<td><?php echo substr( $betaling->description, 0, 50 ); ?></td>
			<td><?php echo $betaling->sequenceType; ?></td>
			<td><?php echo $betaling->amount->value; ?></td>
			<td>
				<?php
				if ( 'pending' === $betaling->status ) {
					?>
				<a class="btn btn-success" href="?id=<?php echo $row['id']; ?>&incassostatus=paid#">betalen</a>
				<a class="btn btn-warning" href="?id=<?php echo $row['id']; ?>&incassostatus=failed#">falen</a>
					<?php
				} else {
					echo $betaling->status;
				}
				?>
			</td>
		</tr>
					<?php
			}
		}
		?>
	</tbody>
</table>

<h3>Refunds</h3>
<table class="table table-striped table-hover table-condensed" data-order='[[ 0, "desc" ]]' >
	<thead>
		<tr>
			<th scope="col">id</th>
			<th scope="col">order_id</th>
			<th scope="col">description</th>
			<th scope="col">value</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
			<?php
			while ( $row = $refunds->fetchArray() ) {
				$betaling = json_decode( $row['data'] );
				?>
		<tr>
			<td><?php echo $row['id']; ?></td>
			<td><?php echo $betaling->metadata->order_id; ?></td>
			<td><?php echo substr( $betaling->description, 0, 50 ); ?></td>
			<td><?php echo $betaling->amount->value; ?></td>
			<td>
				<?php
				if ( 'queued' === $betaling->status ) {
					?>
				<a class="btn btn-success"
					href="?id=<?php echo $row['id']; ?>&refundstatus=pending#">terugstorten</a><a
					class="btn btn-warning" href="?id=<?php echo $row['id']; ?>&refundstatus=failed#">falen</a>
					<?php
				} elseif ( 'pending' === $betaling->status ) {
					?>
				<a class="btn btn-success"
					href="?id=<?php echo $row['id']; ?>&refundstatus=refunded#">teruggestort</a><a
					class="btn btn-warning" href="?id=<?php echo $row['id']; ?>&refundstatus=failed#">falen</a>
					<?php

				} else {
					echo $betaling->status;
				}
				?>
			</td>
		</tr>
				<?php
			}
			?>
	</tbody>
</table>
			<?php
			return ob_get_clean();
}

