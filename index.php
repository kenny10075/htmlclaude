<?php
// 启用错误报告（在生产环境中请移除或注释掉）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 包含数据库配置文件
include "../inc/dbinfo.inc"; // 包含您的数据库连接常量

/* 连接到MySQL并选择数据库 */
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

if (mysqli_connect_errno()) {
    echo "连接MySQL失败: " . mysqli_connect_error();
    exit();
}

/* 确保EMPLOYEES表存在并包含所需字段 */
VerifyEmployeesTable($connection, DB_DATABASE);

/* 如果输入字段已填充，向EMPLOYEES表添加一行 */
$employee_name = isset($_POST['name']) ? htmlentities($_POST['name']) : '';
$employee_gender = isset($_POST['gender']) ? htmlentities($_POST['gender']) : '';
$employee_phone = isset($_POST['phone']) ? htmlentities($_POST['phone']) : '';
$employee_address = isset($_POST['address']) ? htmlentities($_POST['address']) : '';
$employee_email = isset($_POST['email']) ? htmlentities($_POST['email']) : '';

if (!empty($employee_name) && !empty($employee_gender) && !empty($employee_phone) && !empty($employee_address) && !empty($employee_email)) {
    AddEmployee($connection, $employee_name, $employee_gender, $employee_phone, $employee_address, $employee_email);
}

/* 检索员工列表 */
$employee_list = GetEmployeeList($connection);

/* 关闭数据库连接 */
mysqli_close($connection);

/* 函数：验证表是否存在，如不存在则创建 */
function VerifyEmployeesTable($connection, $dbName)
{
    if (!TableExists("EMPLOYEES", $connection, $dbName)) {
        $query = "CREATE TABLE EMPLOYEES (
            ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            NAME VARCHAR(50) NOT NULL,
            GENDER VARCHAR(10),
            PHONE VARCHAR(15),
            ADDRESS VARCHAR(100),
            EMAIL VARCHAR(50) NOT NULL
        )";
        if (!mysqli_query($connection, $query)) {
            echo "<p>创建表时出错: " . mysqli_error($connection) . "</p>";
        }
    } else {
        AddMissingColumns($connection);
    }
}

/* 函数：检查并添加缺失的列 */
function AddMissingColumns($connection)
{
    $columns = [
        'GENDER' => "ALTER TABLE EMPLOYEES ADD COLUMN GENDER VARCHAR(10)",
        'PHONE' => "ALTER TABLE EMPLOYEES ADD COLUMN PHONE VARCHAR(15)",
        'ADDRESS' => "ALTER TABLE EMPLOYEES ADD COLUMN ADDRESS VARCHAR(100)",
        'EMAIL' => "ALTER TABLE EMPLOYEES ADD COLUMN EMAIL VARCHAR(50) NOT NULL"
    ];

    foreach ($columns as $column => $alterQuery) {
        $result = mysqli_query($connection, "SHOW COLUMNS FROM EMPLOYEES LIKE '$column'");
        if (mysqli_num_rows($result) == 0) {
            if (!mysqli_query($connection, $alterQuery)) {
                echo "<p>添加 $column 列时出错: " . mysqli_error($connection) . "</p>";
            }
        }
    }
}

/* 函数：检查表是否存在 */
function TableExists($tableName, $connection, $dbName)
{
    $tableName = mysqli_real_escape_string($connection, $tableName);
    $dbName = mysqli_real_escape_string($connection, $dbName);

    $query = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$tableName'";
    $result = mysqli_query($connection, $query);

    return mysqli_num_rows($result) > 0;
}

/* 函数：向表中添加员工 */
function AddEmployee($connection, $name, $gender, $phone, $address, $email)
{
    $query = "INSERT INTO EMPLOYEES (NAME, GENDER, PHONE, ADDRESS, EMAIL) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'sssss', $name, $gender, $phone, $address, $email);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "<p>信息提交成功！</p>";
    } else {
        echo "<p>提交信息时出错: " . mysqli_error($connection) . "</p>";
    }
    mysqli_stmt_close($stmt);
}

/* 函数：获取员工列表 */
function GetEmployeeList($connection)
{
    $query = "SELECT ID, NAME, GENDER, PHONE, ADDRESS, EMAIL FROM EMPLOYEES";
    $result = mysqli_query($connection, $query);
    $employees = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        mysqli_free_result($result);
    } else {
        echo "<p>检索数据时出错: " . mysqli_error($connection) . "</p>";
    }

    return $employees;
}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Astral by HTML5 UP</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
	</head>
	<body class="is-preload">

		<!-- 包装器-->
			<div id="wrapper">

				<!-- 导航 -->
					<nav id="nav">
						<a href="#" class="icon solid fa-home"><span>主页</span></a>
						<a href="#work" class="icon solid fa-folder"><span>作品</span></a>
						<a href="#contact" class="icon solid fa-envelope"><span>联系</span></a>
						<a href="#list" class="icon solid fa-list"><span>留言列表</span></a>
					</nav>

				<!-- 主体 -->
					<div id="main">

						<!-- 自我介绍，包含添加员工和员工列表 -->
							<article id="home" class="panel intro">
								<header>
									<h1>Jane Doe</h1>
									<p>高级星际投影师</p>
								</header>
								<a href="#work" class="jumplink pic">
									<span class="arrow icon solid fa-chevron-right"><span>查看我的作品</span></span>
									<img src="images/me.jpg" alt="" />
								</a>

								<!-- 添加员工 -->
								<section id="contact">
									<header>
										<h2>添加员工</h2>
									</header>
									<form action="" method="post">
										<div>
											<div class="row">
												<div class="col-6 col-12-medium">
													<input type="text" name="name" placeholder="姓名" required />
												</div>
												<div class="col-6 col-12-medium">
													<select name="gender" required>
														<option value="">选择性别</option>
														<option value="男">男</option>
														<option value="女">女</option>
													</select>
												</div>
												<div class="col-6 col-12-medium">
													<input type="text" name="phone" placeholder="电话" required />
												</div>
												<div class="col-6 col-12-medium">
													<input type="text" name="address" placeholder="居住地址" required />
												</div>
												<div class="col-12">
													<input type="email" name="email" placeholder="电子邮件" required />
												</div>
												<div class="col-12">
													<input type="submit" value="添加员工" />
												</div>
											</div>
										</div>
									</form>
								</section>

								<!-- 员工列表 -->
								<section id="list">
									<header>
										<h2>员工列表</h2>
									</header>
									<?php if (!empty($employee_list)) { ?>
										<table>
											<thead>
												<tr>
													<th>ID</th>
													<th>姓名</th>
													<th>性别</th>
													<th>电话</th>
													<th>居住地址</th>
													<th>电子邮件</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($employee_list as $employee) { ?>
													<tr>
														<td><?php echo htmlspecialchars($employee['ID']); ?></td>
														<td><?php echo htmlspecialchars($employee['NAME']); ?></td>
														<td><?php echo htmlspecialchars($employee['GENDER']); ?></td>
														<td><?php echo htmlspecialchars($employee['PHONE']); ?></td>
														<td><?php echo htmlspecialchars($employee['ADDRESS']); ?></td>
														<td><?php echo htmlspecialchars($employee['EMAIL']); ?></td>
														</tr>
												<?php } ?>
											</tbody>
										</table>
									<?php } else { ?>
										<p>目前没有员工记录。</p>
									<?php } ?>
								</section>
							</article>

						<!-- 作品 -->
							<article id="work" class="panel">
								<header>
									<h2>作品</h2>
								</header>
								<p>这里展示了我的一些作品，欢迎欣赏。</p>
								<section>
									<div class="row">
										<div class="col-4 col-6-medium col-12-small">
											<a href="#" class="image fit"><img src="images/pic01.jpg" alt=""></a>
										</div>
										<div class="col-4 col-6-medium col-12-small">
											<a href="#" class="image fit"><img src="images/pic02.jpg" alt=""></a>
										</div>
										<div class="col-4 col-6-medium col-12-small">
											<a href="#" class="image fit"><img src="images/pic03.jpg" alt=""></a>
										</div>
										<!-- 保留原有内容 -->
									</div>
								</section>
							</article>

					</div>

				<!-- 页脚 -->
					<div id="footer">
						<ul class="copyright">
							<li>&copy; 未命名.</li><li>设计: <a href="http://html5up.net">HTML5 UP</a></li>
						</ul>
					</div>

			</div>

		<!-- 脚本 -->
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/browser.min.js"></script>
			<script src="assets/js/breakpoints.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>

	</body>
</html>
