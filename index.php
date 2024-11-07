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

/* 处理删除请求 */
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    DeleteEmployee($connection, $delete_id);
}

/* 处理修改请求 */
if (isset($_POST['update_id'])) {
    $update_id = intval($_POST['update_id']);
    $employee_name = isset($_POST['name']) ? htmlentities($_POST['name']) : '';
    $employee_gender = isset($_POST['gender']) ? htmlentities($_POST['gender']) : '';
    $employee_phone = isset($_POST['phone']) ? htmlentities($_POST['phone']) : '';
    $employee_address = isset($_POST['address']) ? htmlentities($_POST['address']) : '';
    $employee_email = isset($_POST['email']) ? htmlentities($_POST['email']) : '';
    UpdateEmployee($connection, $update_id, $employee_name, $employee_gender, $employee_phone, $employee_address, $employee_email);
}

/* 如果输入字段已填充，向EMPLOYEES表添加一行 */
$employee_name = isset($_POST['name']) ? htmlentities($_POST['name']) : '';
$employee_gender = isset($_POST['gender']) ? htmlentities($_POST['gender']) : '';
$employee_phone = isset($_POST['phone']) ? htmlentities($_POST['phone']) : '';
$employee_address = isset($_POST['address']) ? htmlentities($_POST['address']) : '';
$employee_email = isset($_POST['email']) ? htmlentities($_POST['email']) : '';

if (!empty($employee_name) && !empty($employee_gender) && !empty($employee_phone) && !empty($employee_address) && !empty($employee_email) && !isset($_POST['update_id'])) {
    AddEmployee($connection, $employee_name, $employee_gender, $employee_phone, $employee_address, $employee_email);
}

/* 处理查询请求 */
$search_query = isset($_POST['search']) ? htmlentities($_POST['search']) : '';
$employee_list = empty($search_query) ? GetEmployeeList($connection) : SearchEmployees($connection, $search_query);

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

/* 函数：修改员工 */
function UpdateEmployee($connection, $id, $name, $gender, $phone, $address, $email)
{
    $query = "UPDATE EMPLOYEES SET NAME=?, GENDER=?, PHONE=?, ADDRESS=?, EMAIL=? WHERE ID=?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'sssssi', $name, $gender, $phone, $address, $email, $id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "<p>员工信息已更新！</p>";
    } else {
        echo "<p>更新员工信息时出错: " . mysqli_error($connection) . "</p>";
    }
    mysqli_stmt_close($stmt);
}

/* 函数：删除员工 */
function DeleteEmployee($connection, $id)
{
    $query = "DELETE FROM EMPLOYEES WHERE ID = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo "<p>员工已删除！</p>";
    } else {
        echo "<p>删除员工时出错: " . mysqli_error($connection) . "</p>";
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

/* 函数：根据查询条件搜索员工 */
function SearchEmployees($connection, $search_query)
{
    $query = "SELECT ID, NAME, GENDER, PHONE, ADDRESS, EMAIL FROM EMPLOYEES 
              WHERE NAME LIKE ? OR GENDER LIKE ? OR PHONE LIKE ? OR ADDRESS LIKE ? OR EMAIL LIKE ?";
    $like_query = "%{$search_query}%";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, 'sssss', $like_query, $like_query, $like_query, $like_query, $like_query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employees = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        mysqli_free_result($result);
    } else {
        echo "<p>搜索数据时出错: " . mysqli_error($connection) . "</p>";
    }

    mysqli_stmt_close($stmt);
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

						<!-- 查詢功能 -->
						<article id="list" class="panel">
							<header>
								<h2>员工查询</h2>
							</header>
							<form action="" method="post">
								<div class="row">
									<div class="col-9 col-12-medium">
										<input type="text" name="search" placeholder="输入查询条件..." value="<?php echo htmlspecialchars($search_query); ?>"/>
									</div>
									<div class="col-3 col-12-medium">
										<input type="submit" value="搜索" />
									</div>
								</div>
							</form>
							<section>
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
									<p>没有找到匹配的员工记录。</p>
								<?php } ?>
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
