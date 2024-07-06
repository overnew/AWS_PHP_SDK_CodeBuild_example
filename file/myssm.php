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

        use Aws\Ssm\SsmClient;
        use Aws\Exception\AwsException;
        

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

        // Check for existing email
     
        $provider = CredentialProvider::ecsCredentials();
        // Be sure to memoize the credentials
        $memoizedProvider = CredentialProvider::memoize($provider);
        
        
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
            echo "DB Table Name: " . $Table_name . "\n";
        } catch (AwsException $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $dynamodb = new DynamoDbClient([
            'region'  => 'ap-northeast-1',
            'version' => 'latest',
            'credentials' => $memoizedProvider
        ]);
     
        #$Table_name = 'user_table';

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