<?php

namespace Inc\cegal\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Inc\cegal\Api\CegalApiDbManager;
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
		try {
			$request = wp_remote_get($query, [ 'timeout' => 4 ]);
		} catch( ConnectException $connectException ) {
			$error = ['message'=> $connectException->getMessage()];
			error_log( 'Connection exception: ' . $connectException->getMessage() );
			return false;
		} catch ( RequestException $e ) {
			error_log( 'Request exception: ' . $e->getMessage() );
			if ($e->getResponse() instanceof ResponseInterface) {
				$error['statusCode'] = $e->getResponse()->getStatusCode();
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
			return false;
		} catch (\Exception $exception) {
			$error['message'] = 'Error: ' . $exception->getMessage();
			error_log($error['message']);
			return false;
		}

		if ( isset( $request->errors ) && count( $request->errors ) > 0 ) {
			var_dump( $request->errors );
			return false;
		}
		$response = $request['response'];
		if ( $response['code'] != 200 ) return false;

		$xml = simplexml_load_string( $request['body'] );
		// Additional checks to ensure the XML and expected fields are present.
		if (!$xml || empty($xml->PORTADA->IMAGEN_PORTADA) || empty($xml->PORTADA)) {
			// Handle error - log or return false
			return false;
		}
		return [
			'data' => base64_decode( $xml->PORTADA->IMAGEN_PORTADA->__toString() ),
			'image' => $xml->PORTADA->IMAGEN_PORTADA->__toString(),
			'format' => $xml->PORTADA->FORMATO_PORTADA->__toString()
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
		$isbn = str_replace('-','',$isbn);
		$filename = $isbn.".jpg";
		$data = $this->fetch_cover( $isbn );
		if( !$data ){
			error_log('Data from fetch_cover does not exist.');
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
		} else {
			$file_id = $cegalApiDbManager->insertFile( $filepath, $data, $filename );
		}
		return ( $file_id > 0 ) ? get_post( $file_id ): false;
	}

  	/**
  	 * ficha
  	 *
  	 * @param  mixed $isbn
  	 * @return array
  	 */
  	public function ficha( $isbn ): array {
		$query  = $this->query( 'fichalibro.xml.php' , $isbn).'&formato=XML';
		$request = wp_remote_get( $query, [ 'timeout' => 2 ]);
		if ( ! $request->code == 200 ) return [];

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
	 * @param  int $batch_size
	 * @param  int $offset
	 * @return array
	 */
	public function scanProducts( int $batch_size = 1, int $offset = 0 ): array {
		// Read all products
		// Query for all products
		$eans = [];
		$response = [];
		$cegalApiDbManager = new CegalApiDbManager;
		$products = $cegalApiDbManager->getProducts( $batch_size, $offset );
		$hasMore = !empty( $products );
		foreach( $products as $product ) {
			$ean = get_post_meta( $product->get_id(), '_ean', true );
			$this->create_cover( $ean );
			$cegalApiDbManager->set_featured_image_for_product( $product->get_id(), $ean );
			$response[] = [ 'id' => $product->get_id() ];
			array_push( $eans, $ean );
		}
		$response['hasMore'] = $hasMore;
		$response['eans'] = $eans;
		$response['message'] = $batch_size." books have been processed: ";
        return $response ;
    }

}