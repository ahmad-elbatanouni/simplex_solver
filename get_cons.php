<?php
	session_start();
?>

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
			<form action="tables.php" method="post">
				<?php
					if(isset($_POST['var_num'], $_POST['cons_num'])){
					if($_POST['var_num'] > 0 && $_POST['cons_num'] > 0) {
				?>
					<label for="objective">Objective Function : </label>
					<select name="objType">
				      <option value="max">Maximize</option>
				      <option value="min">Minimize</option>
				  	</select>
				<?php
						$varibles = $_POST['var_num'];
						$constraints = $_POST['cons_num'];
						$_SESSION['variables'] = $varibles;
						$_SESSION['constraints'] = $constraints;
						$var_count = 1;
						while ($varibles > 0) {
							if($varibles != 1) {
				?>
								<div id="block">
									<input type="number" autofocus = "autofocus" name="Var_<?php echo 'X' . $var_count; ?>" id="Var_<?php echo 'X' . $var_count; ?>" placeholder="EG: 2" required />
									<?php echo ' X' . $var_count . ' + '; ?>
								</div>
				<?php
							} else {
				?>
							<div id="block">
									<input type="number" name="Var_<?php echo 'X' . $var_count; ?>" id="Var_<?php echo 'X' . $var_count; ?>" placeholder="EG: 2" required />
									<?php echo ' X' . $var_count ?>
								</div>
				<?php
							}
							$varibles--;
							$var_count++;
						}
				?>
				<br />
				<br />
				<br />
				<br />
				<br />
				<br />
				<label for="Constraints">Constraints : </label>
				<?php
						$cons_count = 1;
						while ($constraints > 0) {
							echo 'Constraint #' . $cons_count . ' : ';
							$varibles = $_POST['var_num'];
							$var_count = 1;
							while($varibles > 0) {
								if($varibles != 1) {
				?>
									<div id="block">
										<input type="number" name="<?php echo 'C' . $cons_count . 'X' . $var_count; ?>" placeholder="EG: 2" required />
										<?php echo ' X' . $var_count . ' + ' ?>
									</div>
				<?php
								} else {
				?>
									<div id="block">
										<input type="number" name="<?php echo 'C' . $cons_count . 'X' . $var_count; ?>" placeholder="EG: 2" required />
										<?php echo ' X' . $var_count?>
									</div>
				<?php
								}
								$varibles--;
								$var_count++;
							}
				?>
				
								<div id="selection">
									<select name="const_type<?php echo $cons_count ?>">
										<option value="lt"><=</option>
										<option value="gt">>=</option>
										<option value="equal">=</option>
									</select>
								</div>
								<div id="RHS"> 
									<input type="number" name="<?php echo 'RHS' . $cons_count; ?>" placeholder="EG: 2" required />
								</div>
				<?php
							echo '<br />';
							$constraints--;
							$cons_count++;
						}
						echo '<br /><br /><br /><br />';
						echo '<input type="submit" value="Continue..." name="go"/>';
					} else {
				?>							
							<div id="error">
							<img src="images/<?php echo rand(1, 9); ?>.png" />
								<br />
				<?php
						echo "Only Positive Numbers Are Accepted!";
						?>
							<br />
							<a href="index.php">Home</a>
						<?php
					}
					} else {
						?>
						<script type="text/javascript">window.location.replace("error.php");</script>
						<?php
					}
				?>
							</div>
			</form>
		</div><!--End of form-->
	</div><!--End of body-->
</body>
</html>