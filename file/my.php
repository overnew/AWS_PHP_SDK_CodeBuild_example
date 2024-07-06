<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
</head>
<body>
    <h1>User Profile</h1>

    <?php
    // Start session if not already started
    
        // Connect to DynamoDB using AWS SDK for PHP
    require 'vendor/autoload.php';

    use Aws\DynamoDb\Exception\DynamoDbException;
    use Aws\Credentials\CredentialProvider;
    use Aws\DynamoDb\DynamoDbClient;
    use Aws\Ssm\SsmClient;
    use Aws\Exception\AwsException;

    use Aws\DynamoDb\SessionHandler;
    
    $provider = CredentialProvider::ecsCredentials();
            // Be sure to memoize the credentials
            $memoizedProvider = CredentialProvider::memoize($provider);
         
            $dynamodb = new DynamoDbClient([
                'region'  => 'ap-northeast-1',
                'version' => 'latest',
                'credentials' => $memoizedProvider
            ]); 

            $ssmClient = new SsmClient([
                'region' => 'ap-northeast-1', // 사용하려는 AWS 리전을 설정하세요.
                'version' => 'latest',
                'credentials' => $memoizedProvider
            ]);
            
            try {
                $result = $ssmClient->getParameter([
                    'Name' => 'db_table_name', // 가져오려는 파라미터의 이름을 입력하세요.
                    'WithDecryption' => false, // 암호화된 파라미터 값을 가져오려면 true로 설정하세요.
                ]);
            
                $Table_name = $result['Parameter']['Value'];
                #echo "DB Table Name: " . $Table_name . "\n";
            } catch (AwsException $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }

    
    $sessionHandler = SessionHandler::fromClient($dynamodb, [
        'table_name' => 'sessions',
        'hash_key'                      => 'id',
        'session_lifetime' => 1000,
    ]);

    $sessionHandler->register();
    session_start();

    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get user ID from session
        $userId = $_SESSION['user_id'];
        //echo $userId;
        /*
        $provider = CredentialProvider::instanceProfile();
            // Be sure to memoize the credentials
            $memoizedProvider = CredentialProvider::memoize($provider);
         
            $dynamodb = new DynamoDbClient([
                'region'  => 'ap-northeast-1',
                'version' => 'latest',
                'credentials' => $memoizedProvider
            ]);*/

            #$Table_name = 'user_table';
        // Retrieve user data from DynamoDB
        try {
            $result = $dynamodb->getItem([
                'TableName' =>  $Table_name,
                'Key' => [
                    'email' => ['S' => $userId],
                ],
            ]);
        } catch (DynamoDbException $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
            exit;
        }

        // Check if user data exists
        if (isset($result['Item'])) {
            $userData = $result['Item'];

            // Extract and display information
            $email = $userData['email']['S'];
            $name = $userData['name']['S'];
            #$password = $userData['password']['S'];
            echo "<p>이메일: " . $email . "</p>";
            echo "<p>이름: " . $name . "</p>";
            #echo "<p>비밀번호: " . $password . "</p>";
        } else {
            echo "<p style='color: red;'>User not found.</p>";
        }
    } else {
        // User is not logged in, redirect to login page
        echo "<p>You must be logged in to access this page.</p>";
        echo "<a href='signin.php'>로그인</a>";
    }
    ?>
</body>
</html>
