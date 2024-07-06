<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<body>
    <h1>로그인</h1>

    <section id="login">
        <h2>로그인</h2>

        <?php
            // Check user credentials
            require 'vendor/autoload.php';

            use Aws\DynamoDb\Exception\DynamoDbException;
            use Aws\Credentials\CredentialProvider;
            use Aws\DynamoDb\DynamoDbClient;

            use Aws\Ssm\SsmClient;
            use Aws\Exception\AwsException;
            use Aws\DynamoDb\SessionHandler;
        // Check if the form has been submitted
        if (isset($_POST['login'])) {
            // Extract form data
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Validate form data (basic example)
            if (empty($email) || empty($password)) {
                echo "<p style='color: red;'>이메일과 비밀번호를 입력해야 합니다.</p>";
                exit;
            }



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
                #echo "DB Table Name: " . $Table_name . "\n";
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
                    'TableName' => $Table_name,
                    'Key' => [
                        'email' => ['S' => $email],
                    ],
                ]);
            } catch (DynamoDbException $e) {
                echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
                exit;
            }

            if (!isset($result['Item'])) {
                echo "<p style='color: red;'>존재하지 않는 사용자입니다.</p>";
                exit;
            }

            $userData = $result['Item'];

            // Verify password
            if (!password_verify($password, $userData['password']['S'])) {
                echo "<p style='color: red;'>비밀번호가 일치하지 않습니다.</p>";
                exit;
            }

            // Login successful
            // Start session (example)
            $sessionHandler = SessionHandler::fromClient($dynamodb, [
                'table_name' => 'sessions',
                'hash_key'                      => 'id',
                'session_lifetime' => 1000,
            ]);

            $sessionHandler->register();

            session_start();

            $_SESSION['user_id'] = $userData['email']['S'];
            
            // Close the session (optional, but recommended)
            session_write_close();
            echo "<p style='color: green;'>로그인되었습니다.</p>";
            header('Location: ./my.php');
            
            // Alter the session data
            //$_SESSION['user.name'] = 'jeremy';
            //$_SESSION['user.role'] = 'admin';

            //session_start();
            //$_SESSION['user_id'] = $userData['id']['S'];

            // Login successful message
        }
        ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="login_form">
            <label for="email">이메일:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">비밀번호:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" name="login">로그인</button>
        </form>
    </section>
</body>
</html>
