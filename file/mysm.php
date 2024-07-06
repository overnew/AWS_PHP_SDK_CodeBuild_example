<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>롤백테스트</title>
</head>
<body>
    <h1>롤백테스트</h1>

    <?php
        require 'vendor/autoload.php';

        use Aws\DynamoDb\Exception\DynamoDbException;
        use Aws\Credentials\CredentialProvider;
        use Aws\DynamoDb\DynamoDbClient;

        use Aws\SecretsManager\SecretsManagerClient;
        use Aws\Exception\AwsException;
// Check for existing email
 

    // Check if the form has been submitted
    if (isset($_POST['submit'])) {
        // Extract form data
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Validate form data (basic example)
        if (empty($name) || empty($email) || empty($password)) {
            echo "<p style='color: red;'>모든 필드를 입력해야 합니다.</p>";
            exit;
        }

            
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
        echo "<p style='color: red;'>Error: " . $result . "</p>";

        $secret = $result['SecretString'];

    } catch (AwsException $e) {
        // Output error message if fails
        error_log($e->getMessage());
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        return null;
    }

    
    echo "<p style='color: red;'> let go</p>";
    echo "<p style='color: red;'>sc: " . $secret . "</p>";
    echo "<p style='color: red;'>get: " . $secret['table_name'] . "</p>";

        $dynamodb = new DynamoDbClient([
            'region'  => 'ap-northeast-1',
            'version' => 'latest',
            'credentials' => $memoizedProvider
        ]);
         
        $Table_name = $secret['table_name'];

        try {
            $result = $dynamodb->getItem([
                'TableName' =>  $Table_name,
                'Key' => [
                    'email' => ['S' => $email],
                ],
            ]);
        } catch (DynamoDbException $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
            exit;
        }

        if (isset($result['Item'])) {
            echo "<p style='color: red;'>이미 가입된 이메일입니다.</p>";
            exit;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Store user data in DynamoDB
        try {
            $dynamodb->putItem([
                'TableName' =>  $Table_name,
                'Item' => [
                    'id' => ['S' => uniqid()],
                    'name' => ['S' => $name],
                    'email' => ['S' => $email],
                    'password' => ['S' => $hashedPassword],
                ],
            ]);
        } catch (DynamoDbException $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
            exit;
        }

        // Registration successful message
        echo "<p style='color: green;'>회원가입이 완료되었습니다.</p>";
    }
    ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <label for="name">이름:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">이메일:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">비밀번호:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" name="submit">회원가입</button>
        <button onclick="history.back()"> 이전 페이지</button>
    </form>
</body>
</html>