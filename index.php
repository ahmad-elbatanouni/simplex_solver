<html>
<head>
	<title>Simplex Solver</title>
	<link rel="stylesheet" type="text/css" href="css/main_style.css">
</head>
<body>
	<div class="header">
		<div id="title">
			<h1>Simplex solver</h1>
		</div><!--End of the title-->
	</div><!--End of the header-->
	<div class="body">
		<div id="form">
			<form action="get_cons.php" method="post">				
				<label for="varNum">Enter The number of decision variables</label>
				<input type="number" name="var_num" id="var_num" placeholder="EG: 2" autofocus = "autofocus" required />
				<label for="consNum">Enter The number of constraints</label>
				<input type="number" name="cons_num" id="cons_num" placeholder="EG: 2" required />
				<p>
					<input type="submit">
				</p>
			</form>
		</div><!--End of form-->
	</div><!--End of body-->
</body>
</html>