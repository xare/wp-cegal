<?php

namespace Inc\cegal\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Inc\cegal\Api\CegalApiDbManager;
use Inc\cegal\Api\CegalApiDbLogManager;
use Inc\cegal\Api\CegalApiDbLinesManager;
use Psr\Http\Message\ResponseInterface;

class CegalApi {

	private $url_host;
  	private $url_path;
	private $cegalSettings;
  	private $url_user;
  	private $url_pass;

	public function __construct(){
		$this->cegalSettings = get_option('cegal_settings');
		$this->url_host = "www.cegalenred.com/";
		$this->url_path = "peticiones";
		$this->url_user = $this->cegalSettings['cegal_user'];
    	$this->url_pass = isset($this->cegalSettings['cegal_pass']) ? $this->cegalSettings['cegal_pass'] : '';

	}

	public function query( $service, $isbn ): string {
		return 'https://'.$this->url_host.$this->url_path.'/'.$service.'?USUARIO='.$this->url_user.'&CLAVE='.$this->url_pass.'&ISBN='.$isbn;
	}

	/**
	 * A partir de un isbn devuelve un array de ids de sinli que tienen disponible un libro
	 * Si nadie lo tiene devuelve un array vacio
	 * @param string $isbn
	 * @return array
	 */
  	public function disponibilidad( string $isbn ): array {
    	$query  = $this->query('disponibilidad.xml.php', $isbn);
    	$request = wp_remote_get($query, array('timeout' => 1));
		$response = $request['response'];
		if ($response['code'] != 200) return [];
		$xml = simplexml_load_string($request->data);

		$distribuidores = [];
		foreach ($xml as $key => $value) {
			if ($value->TIPO_ASOCIADO->__toString() == 'D') {
				$distribuidores[] = $value->ID_SINLI_ASOCIADO->__toString();
			}
		}
		return $distribuidores;
  	}

	/**
   	* fetch_cover
   	* @param string $isbn
	* @return mixed
   	*/
  	public function fetch_cover( string $isbn ): mixed {
    	$query  = $this->query( 'fichalibro.xml.php', $isbn ).'&TIPOFICHA=C';
		$cegalApiDbLinesManager = new CegalApiDbLinesManager;
		$cegalApiDbLinesManager->set_origin_url( $isbn, $query );
		try {
			$response = wp_remote_get($query, [ 'timeout' => 140 ]);
		} catch( ConnectException $connectException ) {
			$error = ['message'=> $connectException->getMessage()];
			error_log( 'Connection exception: ' . $connectException->getMessage() );
			$cegalApiDbLinesManager->setError( $isbn, $error['message'] );
			return false;
		} catch ( RequestException $requestException ) {
			error_log( 'Request exception: ' . $requestException->getMessage() );
			if ($requestException->getResponse() instanceof ResponseInterface) {
				$error['statusCode'] = $requestException->getResponse()->getStatusCode();
				if ( $error['statusCode'] === 404 ) {
					$error['message'] = 'Error: Resource not found';
				} else {
					// Handle other client errors
					$error['message'] = 'Error: Client error - ' . $error['statusCode'];
				}
			} else {
				// Handle other exceptions
				$error['message'] = 'Error: ' . $e->getMessage();
			}
			error_log($error['message']);
			$cegalApiDbLinesManager->setError( $isbn, $error['message'] );
			return false;
		} catch (\Exception $exception) {
			$error['message'] = 'Error: ' . $exception->getMessage();
			error_log($error['message']);
			$cegalApiDbLinesManager->setError( $isbn, $error['message'] );
			return false;
		}

		if ( isset( $response->errors ) && count( $response->errors ) > 0 ) {
			var_dump( $response->errors );
			$errorString = '';
			foreach($response->errors as $error) {
				$errorString .= ' ' . $error;
			}
			$cegalApiDbLinesManager->setError( $isbn, $errorString );
			return false;
		}

		$xmlString = wp_remote_retrieve_body( $response );
		$xmlString = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/', '', $xmlString);
		$xml = simplexml_load_string( $xmlString );
		// Additional checks to ensure the XML and expected fields are present.
		if ( !$xml || empty( $xml->LIBRO->PORTADA->IMAGEN_PORTADA ) || empty( $xml->LIBRO->PORTADA ) ) {
			// Handle error - log or return false
			error_log( 'Error: XML or expected fields not found.' );
			$cegalApiDbLinesManager->setError( $isbn, 'Error: XML or expected fields not found.' );
			return false;
		}
		return [
			'data' => base64_decode( (string) $xml->LIBRO->PORTADA->IMAGEN_PORTADA ),
			'image' => (string) $xml->LIBRO->PORTADA->IMAGEN_PORTADA,
			'format' => (string) $xml->LIBRO->PORTADA->FORMATO_PORTADA
		];
  	}

    /**
	 * Checks if the cover exists and if it does returns the file object.
	 * It it doesn't exists downloads it and creates the object
	 *
	 * @param string $isbn
	 * @return mixed
	 */

	public function create_cover(string $isbn): mixed {
		$cegalApiDbLinesManager = new CegalApiDbLinesManager;
		$isbn = str_replace('-','',$isbn);
		$filename = $isbn.".jpg";
		$data = $this->fetch_cover( $isbn );
		if( !$data ){
			error_log('Data from fetch_cover does not exist.');
			$cegalApiDbLinesManager->setError( $isbn, 'Data from fetch_cover does not exist.' );
			return false;
		}

		$filepath = sprintf( "%s/portadas/%s", wp_upload_dir()[ 'basedir' ], $filename );
		// First, check if the image exists in the database
		$cegalApiDbManager = new CegalApiDbManager;
		$existing_file = $cegalApiDbManager->isAttachment( $filename );

		if ( $existing_file && file_exists( $filepath ) ) {
			wp_update_post([
				'ID' => $existing_file[0]->ID,
				'post_modified' => current_time('mysql'),
            	'post_modified_gmt' => get_gmt_from_date( current_time('mysql') ),
				'guid' => preg_replace( '/\/\d{4}\/\d{1,2}\/portadas\//', '/portadas/', $existing_file[0]->guuid ),
			]);
			$file_id = $existing_file[0]->ID;
			$cegalApiDbLinesManager->setError( $isbn, 'File.'. $file_id.' already exists.' );
		} else {
			$file_id = $cegalApiDbManager->insertFile( $filepath, $data, $filename );
		}
		return ( $file_id > 0 ) ? get_post( $file_id ): false;
	}

  	/**
  	 * ficha
  	 *
  	 * @param  mixed $isbn
  	 * @return mixed
  	 */
  	public function ficha( $isbn ): mixed {
		$query  = $this->query( 'fichalibro.xml.php' , $isbn).'&formato=XML';
		$request = wp_remote_get( $query, [ 'timeout' => 2 ]);
		if ( ! $request->code == 200 ) return false;

		$xml = simplexml_load_string( $request->data );

		foreach ( $xml as $key => $value ) {
			//dpm();
			$book = array();
			$book['isbn'] = $value->ISBN->__toString();
			$book['title'] = $value->TITULO->__toString();
			$book['ean'] = $value->EAN->__toString();
			$book['price'] = $value->PRECIO_CON_IVA->__toString();
			$book['year'] = substr($value->FECHA_PUBLICACION->__toString(), 2);
			$book["pages"] = $value->NUMERO_PAGINAS->__toString();
			$book['description'] = $this->sinopsis($isbn);
			$book['portada'] = $this->create_cover($isbn);
		}
		return $book;
   	}


	/**
	 * sinopsis
	 *
	 * @param  mixed $isbn
	 * @return string
	 */
	public function sinopsis( $isbn ): string {
		$query  = $this->query('fichalibro.xml.php', $isbn).'&TIPOFICHA=C';
		$request = wp_remote_get($query, array('timeout' => 2));

		if ($request->code != 200) return [];
		$xml = simplexml_load_string($request->data);

		foreach ( $xml as $key => $value ) {
			$sinopsis = $value->RESUMEN->__toString();
		}
		return $sinopsis;
	}

	/**
	 * scanProducts
	 *
	 * @return string
	 */
	public function scanProducts( $log_id, $batch_size = -1, $offset = 0): string {
		global $wpdb;
		// Read all products
		// Query for all products
		$cegalApiDbManager = new CegalApiDbManager;
		$cegalApiDbLogManager = new CegalApiDbLogManager;
		$cegalApiDbLinesManager = new CegalApiDbLinesManager;
		// Read all products.
		// Query for all products.
		$batch_size = (isset($_POST['batch_size']) && $_POST['batch_size'] != null) ? $_POST['batch_size'] : $batch_size;
		$offset = (isset($_POST['offset']) && $_POST['offset'] != null) ? $_POST['offset']: $offset;
		/* $args = [
			'status' => 'publish',
			'limit' => $batch_size,
			'offset' => $offset
		];
		$products = wc_get_products($args); */
		$query = 'SELECT * FROM {$wpdb->posts}
			WHERE ID NOT IN (
				SELECT post_id from {$wpdb->postmeta}
				WHERE meta_key = "_thumbnail_id"
			)
			AND post_type = "product"
			AND post_status = "publish"
			LIMIT {$offset}, {$batch_size}';
		$products = $wpdb->get_results( $query, OBJECT_K );
		$eans = [];
		$hasMore = !empty( $products );
		$totalLines = $cegalApiDbManager->countAllProducts();
		$progress = 0;
		foreach( $products as $product ) {
			error_log('Inside Cron. Product ID: '. $product->ID );
			$ean = get_post_meta( $product->ID, '_ean', true );
			if ($this->validateEAN($ean) == false) continue;
			error_log('Inside Cron. EAN: '. $ean );
			$filepath = sprintf("%s/portadas/%s", wp_upload_dir()['basedir'], $ean.'.jpg');
			error_log('Inside Cron. Filepath: '. $filepath );
			$line_id = $cegalApiDbLinesManager->insertLinesData($log_id, $ean, $filepath);
			if ( $cegalApiDbManager->hasAttachment( $product->ID ) ) {
				error_log('This product with EAN: '. $ean . ' has already a cover.' );
				$cegalApiDbLinesManager->setError( $ean, 'This product has already a cover.' );
				continue;
			}
			if ($file = $this->create_cover( $ean )) {
			    $cegalApiDbManager->set_featured_image_for_product( $file->ID, $ean );
			    $cegalApiDbLinesManager->setBook($product->post_title, $product->ID, $line_id);
            }
			$cegalApiDbLogManager->setLogStatus($log_id, 'processed');
			$response[] = [ 'id' => $product->get_id() ];
			error_log('Offset now: '. $offset );
			$progress = ( $offset / $totalLines ) * 100;
			error_log('Progress now: '. $progress );
			array_push( $eans, $ean );
		}
		$response['hasMore'] = $hasMore;
		$response['eans'] = $eans;
		$response['message'] = $batch_size." books have been processed: ";
        $response['progress'] = number_format($progress, 2)." %";
		return json_encode( $response );
    }


	function validateEAN($ean) {
		// Check if the EAN is a valid length and contains only digits
		if (strlen($ean) != 13 || !ctype_digit($ean)) {
			return false;
		}

		// Split the EAN into its individual digits
		$digits = str_split($ean);

		// Calculate the checksum
		$sum = 0;
		foreach ($digits as $position => $digit) {
			if ($position < 12) { // Exclude the last digit (check digit) from calculation
				if ($position % 2 == 0) { // Even-positioned digits (considering the first digit as position 0)
					$sum += $digit; // Add directly
				} else { // Odd-positioned digits
					$sum += 3 * $digit; // Multiply by 3 and add
				}
			}
		}

		// Calculate the check digit and compare with the last digit of the EAN
		$checkDigit = (10 - ($sum % 10)) % 10;

		// Return true if the check digit matches the last digit, false otherwise
		return $checkDigit == $digits[12];
	}

}