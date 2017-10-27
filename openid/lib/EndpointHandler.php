<?php

// Decodes the data array and returns the value of "claim"
function getClaim($claim, $data) {
	$data_array = json_decode($data, true);
	return (array_key_exists($claim, $data_array)) ? $data_array[$claim] : "";
	//return $data_array[$claim];
}

// A class to handle both fetching and sending data to the various endpoints
class EndpointHandler {
	
	private $metadata = "";
	private $metadata_endpoint = "";
	
	public function __construct($tenant, $policy_name) {
		$this->getMetadata($tenant, $policy_name);
	}
	
	// Fetches the data at an endpoint using a HTTP GET request
	public function getEndpointData($uri) {
		return file_get_contents($uri);
	}
	
	// Using a HTTP POST request, sends data to the endpoint and receives the response
	public function postEndpointData($uri, $fields) {
		$context  = stream_context_create($fields);
		return file_get_contents($uri, false, $context);
	}
	
	// Given a B2C policy name, constructs the metadata endpoint 
	// and fetches the metadata from that endpoint
	public function getMetadata($tenant, $policy_name) {
        $metadata_endpoint = sprintf('https://login.microsoftonline.com/%s.onmicrosoft.com/v2.0/.well-known/openid-configuration?p=%s', 
                             $tenant, $policy_name);
		$this->metadata = $this->getEndpointData($metadata_endpoint);
	}
	
	// Returns the value of the issuer claim from the metadata
	public function getIssuer() {
		$iss = getClaim("issuer", $this->metadata);
		return $iss;	
	}
	
	// Returns the value of the jwks_uri claim from the metadata
	public function getJwksUri() {
		$jwks_uri = getClaim("jwks_uri", $this->metadata);
		return $jwks_uri;	
	}
	
	// Returns the data at the jwks_uri page
	public function getJwksUriData() {
		$jwks_uri = $this->getJwksUri();
		$key_data = $this->getEndpointData($jwks_uri);
		return $key_data;
	}
	
	// Obtains the authorization endpoint from the metadata
	// and adds the necessary query arguments
	public function getAuthorizationEndpoint($response_type, $response_mode, $redirect_uri, $clientID, $scope) {
		$authorization_endpoint = getClaim("authorization_endpoint", $this->metadata).
											'&response_type='.$response_type.
											'&client_id='.$clientID.
											'&redirect_uri='.$redirect_uri.
											'&response_mode='.$response_mode.
											'&scope='.$scope;
		return $authorization_endpoint;
	}
	
	// Obtains the end session endpoint from the metadata
	// and adds the necessary query arguments
	public function getEndSessionEndpoint() {
		$end_session_endpoint = getClaim("end_session_endpoint", $this->metadata).
																'&redirect_uri='.$redirect_uri;
		return $end_session_endpoint;
	}
	
	// Obtains the token endpoint from the metadata
	public function getTokenEndpoint() {
		$token_endpoint = getClaim("token_endpoint", $this->metadata);
		return $token_endpoint;
	}
}

?>
