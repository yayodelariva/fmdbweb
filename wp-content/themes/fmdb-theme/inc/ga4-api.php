<?php
/**
 * GA4 Data API v1 helper — service-account JWT auth + report queries.
 *
 * Configuration (set once via WP-CLI on the server):
 *
 *     wp option update fmdb_ga4_property_id 541893240
 *     wp option update fmdb_ga4_credentials_path /home/udmbypmy/credentials/fmdb-web-fbc920b0f61d.json
 */

class FMDB_GA4_API {

    private string $credentials_path;
    private string $property_id;
    private ?string $access_token = null;

    public function __construct( string $credentials_path, string $property_id ) {
        $this->credentials_path = $credentials_path;
        $this->property_id      = $property_id;
    }

    public static function from_options(): ?self {
        $path = trim( (string) get_option( 'fmdb_ga4_credentials_path', '' ) );
        $prop = trim( (string) get_option( 'fmdb_ga4_property_id', '' ) );
        if ( $path === '' || $prop === '' ) return null;
        if ( ! file_exists( $path ) ) return null;
        return new self( $path, $prop );
    }

    public function authenticate(): bool {
        $creds = json_decode( file_get_contents( $this->credentials_path ), true );
        if ( ! $creds || ! isset( $creds['client_email'], $creds['private_key'], $creds['token_uri'] ) ) {
            return false;
        }

        $now     = time();
        $header  = $this->base64url( json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
        $payload = $this->base64url( json_encode( [
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud'   => $creds['token_uri'],
            'iat'   => $now,
            'exp'   => $now + 3600,
        ] ) );

        $input = $header . '.' . $payload;
        if ( ! openssl_sign( $input, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256 ) ) {
            return false;
        }

        $jwt = $input . '.' . $this->base64url( $signature );

        $response = wp_remote_post( $creds['token_uri'], [
            'body'    => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) return false;

        $body               = json_decode( wp_remote_retrieve_body( $response ), true );
        $this->access_token = $body['access_token'] ?? null;
        return $this->access_token !== null;
    }

    public function run_report( array $body ): ?array {
        if ( ! $this->access_token ) return null;

        $url = sprintf(
            'https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport',
            $this->property_id
        );

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode( $body ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) return null;

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) return null;

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    /**
     * Extract a simple dimension → metric map from a single-dimension report.
     */
    public function pluck( ?array $result, int $metric_index = 0 ): array {
        $map = [];
        foreach ( ( $result['rows'] ?? [] ) as $row ) {
            $key       = $row['dimensionValues'][0]['value'] ?? '';
            $map[$key] = (float) ( $row['metricValues'][$metric_index]['value'] ?? 0 );
        }
        return $map;
    }

    /**
     * Extract totals from a dimensionless report (or the totals row).
     */
    public function totals( ?array $result ): array {
        $vals = [];
        foreach ( ( $result['totals'][0]['metricValues'] ?? [] ) as $mv ) {
            $vals[] = (float) ( $mv['value'] ?? 0 );
        }
        return $vals;
    }

    private function base64url( string $data ): string {
        return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
    }
}
