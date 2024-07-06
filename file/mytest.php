<?php
require 'vendor/autoload.php';

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;

function getSecret()
{   
    
     
    $provider = CredentialProvider::ecsCredentials();
    // Be sure to memoize the credentials
    $memoizedProvider = CredentialProvider::memoize($provider);

    $secretName = 'user_table';
    $region = 'ap-northeast-1';

    // Create a Secrets Manager client
    $client = new SecretsManagerClient([
        'version' => 'latest',
        'region' => $region,
        'credentials' => $memoizedProvider
    ]);

    try {
        $result = $client->getSecretValue([
            'SecretId' => $secretName,
        ]);

        // Decrypts secret using the associated KMS key.
        $secret = $result['SecretString'];
        
        return json_decode($secret, true);

    } catch (AwsException $e) {
        // Output error message if fails
        error_log($e->getMessage());
        return null;
    }
}

$secret = getSecret();

if ($secret) {
    $host = $secret['table_name'];

    // Example using PDO to connect to a MySQL database
    try {
        echo "Connected to the database successfully!" . $host;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
} else {
    echo "Failed to retrieve secret.";
}
?>