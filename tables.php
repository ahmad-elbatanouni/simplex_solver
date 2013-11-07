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
	<div id="form">
<?PHP
	$information = array();
	$information[0] = $_SESSION['variables'];
	$information[1] = $_SESSION['constraints'];


	if($_POST['go']) {
	$Obj_type = $_POST['objType'];

	$dec_vars = array();
	$i = 1;

	//Find a way to send data between pages
	while($i <= $information[0]) {
		$dec_vars['V' . $i] = $_POST['Var_X' . $i];
		$i++;
	}

	$original_length = 0;
	for ($x=1; $x <= $information[1]; $x++) { 
		$constraint_type = $_POST['const_type'.$x];
		if($constraint_type == "gt")
			$original_length += 2;
		else
			$original_length++;
	}

	$artificials = array();
	$artificialsCounter = 0;
	$MaximumVal = $dec_vars['V' . getMostPositive($dec_vars, 'V')] + 1000;

	$const_num = 1;
	while($original_length > 0) {
		if($_POST['const_type'.$const_num] == "lt")
			$dec_vars['V'.$i] = 0;
		elseif($_POST['const_type'.$const_num] == "gt"){
			if($Obj_type == "max") {
				$dec_vars['V'.$i++] = 0;
				$dec_vars['V'.$i] = -1 * $MaximumVal;
			} else {
				$dec_vars['V'.$i++] = 0;
				$dec_vars['V'.$i] = $MaximumVal;
			}
			$artificials[$artificialsCounter++] = $i;
			$original_length--;
		}
		else {
			if($Obj_type == "max") {
				$dec_vars['V'.$i] = -1 * $MaximumVal;
			} else {
				$dec_vars['V'.$i] = $MaximumVal;
			}
			$artificials[$artificialsCounter++] = $i;
		}
		$i++;
		$const_num++;
		$original_length--;
	}	


	//*****************************Constraints LHS values*******************
	$const = array();
	$const_types = array();
	//$i => #Constraints
	//$j => #Decision Variables
	$i = 1;	
	$col_num = 1;

	while($i <= $information[1]) {
		$const['C' . $i] = array();
		$j = 1;
		while($j <= $information[0]) {
			$const['C' . $i]['V' . $j] = $_POST['C' . $i . 'X' . $j];
			$j++;
		}
		$const_types[$i] = $_POST['const_type'.$i];
		//****************Edit hena *****************************
		if($_POST['const_type'.$i] == "lt") {
			while($j <= count($dec_vars)) {
				if($col_num + $information[0] == $j)
					$const['C' . $i]['V' . $j] = 1;
				else
					$const['C' . $i]['V' . $j] = 0;
				$j++;
			}
			$col_num++;
		} elseif($_POST['const_type'.$i] == "gt") {
			$edited = false;
			while($j <= count($dec_vars)) {				
				if($information[0]  + $col_num == $j && $edited == false) {
					$const['C' . $i]['V' . $j] = -1;
					$const['C' . $i]['V' . ++$j] = 1;
					$col_num += 2;
					$edited = true;
				}
				else {
					$const['C' . $i]['V' . $j] = 0;
				}
				$j++;

			}
		} else {
			while($j <= count($dec_vars)) {
				if($col_num + $information[0] == $j)
					$const['C' . $i]['V' . $j] = 1;
				else
					$const['C' . $i]['V' . $j] = 0;
				$j++;
			}
			$col_num++;
		}
		$i++;
		
	}



	//*****************************Constraints RHS values*******************
	$RHS = array();
	//$i => #Constraints
	//$j => #Decision Variables
	$i = 1;	
	while($i <= $information[1]) {
		$RHS['RHS' . $i] = $_POST['RHS' . $i];
		$i++;
	}


	//****************************Generation of Zj***************************
	$added_cols = 0;
	for($counter = 1; $counter <= count($const_types); $counter++) {
		if($const_types[$counter] == "gt")
			$added_cols += 2;
		else
			$added_cols++;
	}
	//$i indicates number of decision Variables
	$Zj = array();
	$count = 1;
	while($count <= count($dec_vars)) {
		$sum = 0;
		$j = 1;//indicates #constraints
		$i = count($dec_vars) - $added_cols + 1;
		while($j <= count($const)) {
			if($const_types[$j] == "gt") {
				$sum += $const['C'.$j]['V'.$count] * $dec_vars['V'.++$i];
			} else {
				$sum += $const['C'.$j]['V'.$count] * $dec_vars['V'.$i];
			}
			$j++;
			$i++;
		}
		$Zj['Zj'.$count] = $sum;
		$count++;
	}
	//************************End of Generation of Zj************************

	//****************************Generation of Cj - Zj**********************
	$CjMinusZj = array();
	$count = 1;
	while($count <= count($dec_vars)) {
		$CjMinusZj['CMZ'.$count] = $dec_vars['V'.$count] - $Zj['Zj'.$count];
		$count++;
	}
	//************************End of Generation of Cj - Zj*******************


	//************************Generating Theta values************************
	$Theta = array();
	$count = 1;
	if($Obj_type == "max")
		$PivotCol = getMostPositive($CjMinusZj, 'CMZ');
	elseif($Obj_type == "min")
		$PivotCol = getMostNegative($CjMinusZj, 'CMZ');

	while ($count <= count($const)) {
		if(($const['C'.$count]['V'.$PivotCol] > 0 && $RHS['RHS'.$count] > 0) || ($const['C'.$count]['V'.$PivotCol] < 0 && $RHS['RHS'.$count] < 0))
			$Theta['T'.$count] = $RHS['RHS'.$count] / $const['C'.$count]['V'.$PivotCol];
		else
			$Theta['T'.$count] = Null;
		$count++;
	}
	//***********************************************************************
	//***********************************************************************
	//***********************************************************************
	//***********************************************************************
	//******************Start of Dispaying Standard Form*********************
	//***********************************************************************
	//***********************************************************************
	//***********************************************************************
	if($Obj_type == "max") {
		echo '<p><div class="objective"><p><div class="num">Maximize</div> f = ';
		for ($i=1; $i <= count($dec_vars); $i++) { 
			if($i < count($dec_vars)) {
				if($dec_vars['V'.$i] == -1 * $MaximumVal)
					echo '<div class="num">-M</div> X'.$i.' + ';
				else
					echo '<div class="num">'.$dec_vars['V'.$i].'</div> X'.$i.' + ';
			}
			else {
				if($dec_vars['V'.$i] == -1 * $MaximumVal)
					echo '<div class="num">-M</div> X'.$i;
				else
					echo '<div class="num">'.$dec_vars['V'.$i].'</div> X'.$i;
			}
		}
		echo '</p><p><em>Subject To : </em></p>';
		for ($i=1; $i <= $information[1]; $i++) { 
			echo '<p>';
			for ($j=1; $j <= count($dec_vars); $j++) {
				if($j < count($dec_vars))
					echo '<div class="num">'.$const['C'.$i]['V'.$j].'</div> X'.$j.' + ';
				else 
					echo '<div class="num">'.$const['C'.$i]['V'.$j].'</div> X'.$j;
			}
			echo ' = <div class="num">'.$RHS['RHS'.$i].'</div></p>';
		}
	} else {
		echo '<p><div class="objective"><div class="num">Minimize</div> f = ';
		for ($i=1; $i <= count($dec_vars); $i++) { 
			if($i < count($dec_vars)) {
				if($dec_vars['V'.$i] == $MaximumVal)
					echo '<div class="num">M</div> X'.$i.' + ';
				else
					echo '<div class="num">'.$dec_vars['V'.$i].'</div> X'.$i.' + ';
			}
			else {
				if($dec_vars['V'.$i] == $MaximumVal)
					echo '<div class="num">M</div> X'.$i;
				else
					echo '<div class="num">'.$dec_vars['V'.$i].'</div> X'.$i;
			}
		}
		echo '</p><p><em>Subject To : </em></p>';
		for ($i=1; $i <= $information[1]; $i++) { 
			echo '<p>';
			for ($j=1; $j <= count($dec_vars); $j++) {
				if($j < count($dec_vars))
					echo '<div class="num">'.$const['C'.$i]['V'.$j].'</div> X'.$j.' + ';
				else
					echo '<div class="num">'.$const['C'.$i]['V'.$j].'</div> X'.$j;
			}
			echo ' = <div class="num">'.$RHS['RHS'.$i].'</div></p>';
		}
	}
	echo '<p>';
	for ($i=1; $i <= count($dec_vars); $i++) {
		if($i < count($dec_vars)) 
			echo 'X'.$i.', ';
		else
			echo 'X'.$i;
	}
	echo ' >= 0</p>';
	echo '</div></p>';


	//***********************************************************************
	//***********************************************************************
	//******************End of Dispaying Standard Form***********************
	//***********************************************************************
	//***********************************************************************


	//********************End of Generating Theta values*********************
	$table_num = 0;
	echo '<p><div class="table_num">Tableau Number : <div class="num">'.++$table_num.'</div></div></p>';
	echo '
		<table align="center" id="table">
			<tr>
				<td></td>
				<td></td>';
	$i = 1;
	//*******************Displaying objective coefficients***************
	while($i <= count($dec_vars)) {
		if($dec_vars['V' . $i] == $MaximumVal)
			echo '<td>M</td>';
		elseif($dec_vars['V' . $i] == -1 * $MaximumVal)
			echo '<td>-M</td>';
		else
			echo '<td>' . $dec_vars['V' . $i] . '</td>';
		$i++;
	}
	echo '
				<td></td>
				<td></td>
			</tr>
			<tr>
				<th>Cb</th>
				<th>Xb</th>';
	//****************End of Displaying objective coefficients************
	$i = 1;
	while($i <= count($dec_vars)) {
		echo '<th>X'.$i.'</th>';
		$i++;
	}
	echo '
				<th>b</th>
				<th>&#920;</th>
			</tr>';
	$basic_loc = count($dec_vars) - $added_cols + 1;
	
	$const_num = 1;
	while($const_num <= count($const)) {
		echo'<tr>';
		if($const_types[$const_num] == "gt")
			if($dec_vars['V'.($basic_loc + 1)] == -1 * $MaximumVal)
				echo '<td>-M</td><td>X'.$basic_loc.'</td>';
			elseif($dec_vars['V'.($basic_loc + 1)] == $MaximumVal)
				echo '<td>M</td><td>X'.$basic_loc.'</td>';
			else
				echo '<td>'.$dec_vars['V'.++$basic_loc].'</td><td>X'.$basic_loc.'</td>';
		else
			echo '<td>'.$dec_vars['V'.$basic_loc].'</td><td>X'.$basic_loc.'</td>';
		$i = 1;
		while($i <= count($dec_vars)) {
			echo '<td>'.$const['C'.$const_num]['V'.$i].'</td>';
			$i++;
		}
		echo '<td>'.$RHS['RHS'.$const_num].'</td><td>'.$Theta['T'.$const_num].'</td></tr>';
		$basic_loc++;
		$const_num++;
	}
	echo '<tr><td></td><td>Zj</td>';


	//****************************Displaying Zj*******************************
	$count = 1;
	while($count <= count($Zj)) {
		echo '<td>'.$Zj['Zj'.$count].'</td>';
		$count++;
	}
	echo '<td></td><td></td></tr>';
	//************************End of Displaying Zj****************************

	

	//****************************Displaying Cj - Zj**************************
	echo '<tr><td></td><td>Cj - Zj</td>';
	//$i indicates number of decision Variables
	$count = 1;
	while($count <= count($CjMinusZj)) {
		echo '<td>'.$CjMinusZj['CMZ'.$count].'</td>';
		$count++;
	}
	echo '<td></td><td></td></tr>';
	//************************End of Displaying Cj - Zj***********************
	
	echo '
		</table>';

	$Xb = array();
	$Cb = array();
	$basic_loc = count($dec_vars) - $added_cols + 1;
	for($i = 1; $i <= count($const); $i++, $basic_loc++) {
		if($const_types[$i] == "gt") {
			$Xb[$i] = 'X'.++$basic_loc;
			$Cb[$i] = $dec_vars['V'.$basic_loc];
		} else {
			$Xb[$i] = 'X'.$basic_loc;
			$Cb[$i] = $dec_vars['V'.$basic_loc];
		}

	}
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	if ($Obj_type == "max") {
	while((hasPositive($CjMinusZj, 'CMZ'))) {

		$outGoingRow = getLeastPositive($Theta, 'T');
		if($outGoingRow > $information[1]) {
			$exception =  'Unbounded Feasible Solution.';
			break;
		}

		echo '<p><div class="table_num">Tableau Number : <div class="num">'.++$table_num.'</div></div></p>';
		
		
		$inGoingCol = getMostPositive($CjMinusZj, 'CMZ');
		$pivotLmnt = $const['C'.$outGoingRow]['V'.$inGoingCol];

		$Xb[$outGoingRow] = 'X'.$inGoingCol;
		$Cb[$outGoingRow] = $dec_vars['V'.$inGoingCol];


		$count = 1;
		while($count <= count($dec_vars)) {
			$const['C'.$outGoingRow]['V'.$count] = round(($const['C'.$outGoingRow]['V'.$count] / $pivotLmnt), 5);
			$count++;
		}
		$RHS['RHS'.$outGoingRow] = round(($RHS['RHS'.$outGoingRow] / $pivotLmnt), 5);

		for($i = 1; $i <= count($const); $i++) {
			$toDivideValue = $const['C'.$i]['V'.$inGoingCol] * -1;
			if($i == $outGoingRow)
				continue;
			$pivotParallel = $const['C'.$i]['V'.$inGoingCol];
			for($j = 1; $j <= count($dec_vars); $j++) {
				$const['C'.$i]['V'.$j] += round(($const['C'.$outGoingRow]['V'.$j] * $toDivideValue), 5);
			}
			$RHS['RHS'.$i] += round(($RHS['RHS'.$outGoingRow] * $toDivideValue), 5);
		}

		//****************************Generation of Zj***************************
		$count = 1;
		while($count <= count($dec_vars)) {
			$sum = 0;
			$j = 1;//indicates #constraints
			//$i = count($dec_vars) - count($const) + 1;
			while($j <= count($const)) {
				$sum += $const['C'.$j]['V'.$count] * $Cb[$j];//$dec_vars['V'.$i];
				$j++;
				//$i++;
			}
			$Zj['Zj'.$count] = $sum;
			$count++;
		}
		//************************End of Generation of Zj************************

		//****************************Generation of Cj - Zj**********************
		$count = 1;
		while($count <= count($dec_vars)) {
			$CjMinusZj['CMZ'.$count] = $dec_vars['V'.$count] - $Zj['Zj'.$count];
			$count++;
		}
		//************************End of Generation of Cj - Zj*******************

		$PivotCol = getMostPositive($CjMinusZj, 'CMZ');
		$count = 1;
		while ($count <= count($const)) {
			if(($const['C'.$count]['V'.$PivotCol] > 0 && $RHS['RHS'.$count] > 0) || ($const['C'.$count]['V'.$PivotCol] < 0 && $RHS['RHS'.$count] < 0))
				$Theta['T'.$count] = $RHS['RHS'.$count] / $const['C'.$count]['V'.$PivotCol];
			else
				$Theta['T'.$count] = Null;
			$count++;
		}


		//*************************************************************************************

		echo '
		<table align="center" id="table">
			<tr>
				<td></td>
				<td></td>';
			$i = 1;
			//*******************Displaying objective coefficients***************
			while($i <= count($dec_vars)) {
				if($dec_vars['V' . $i] == -1 * $MaximumVal)
					echo '<td>-M</td>';
				else
					echo '<td>' . $dec_vars['V' . $i] . '</td>';
				$i++;
			}
			echo '
						<td></td>
						<td></td>
					</tr>
					<tr>
						<th>Cb</th>
						<th>Xb</th>';
			//****************End of Displaying objective coefficients************
			$i = 1;
			while($i <= count($dec_vars)) {
				echo '<th>X'.$i.'</th>';
				$i++;
			}
			echo '
						<th>b</th>
						<th>&#920;</th>
					</tr>';
			$const_num = 1;
			while($const_num <= count($const)) {
				if($Cb[$const_num] == -1 * $MaximumVal)
					echo'<tr>
							<td>-M</td><td>'.$Xb[$const_num].'</td>';
				else
					echo'<tr>
						<td>'.$Cb[$const_num].'</td><td>'.$Xb[$const_num].'</td>';
				$i = 1;
				while($i <= count($dec_vars)) {
					echo '<td>'.$const['C'.$const_num]['V'.$i].'</td>';
					$i++;
				}
				echo '<td>'.$RHS['RHS'.$const_num].'</td><td>'.$Theta['T'.$const_num].'</td></tr>';
				$basic_loc++;
				$const_num++;
			}
			echo '<tr><td></td><td>Zj</td>';


			//****************************Displaying Zj*******************************
			$count = 1;
			while($count <= count($Zj)) {
				echo '<td>'.$Zj['Zj'.$count].'</td>';
				$count++;
			}
			echo '<td></td><td></td></tr>';
			//************************End of Displaying Zj****************************

			

			//****************************Displaying Cj - Zj**************************
			echo '<tr><td></td><td>Cj - Zj</td>';
			//$i indicates number of decision Variables
			$count = 1;
			while($count <= count($CjMinusZj)) {
				echo '<td>'.$CjMinusZj['CMZ'.$count].'</td>';
				$count++;
			}
			echo '<td></td><td></td></tr>';
			//************************End of Displaying Cj - Zj***********************
			
			echo '
				</table>';

		//*************************************************************************************
	}//End of the while
	}//End of the if(MAX)

	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************




	elseif ($Obj_type == "min") {
	while((hasNegative($CjMinusZj, 'CMZ'))) {

		$outGoingRow = getLeastPositive($Theta, 'T');
		if($outGoingRow > $information[1]) {
			$exception =  'Unbounded Feasible Solution.';
			break;
		}
		echo '<p><div class="table_num">Tableau Number : <div class="num">'.++$table_num.'</div></div></p>';
		
		$inGoingCol = getMostNegative($CjMinusZj, 'CMZ');
		$pivotLmnt = $const['C'.$outGoingRow]['V'.$inGoingCol];

		$Xb[$outGoingRow] = 'X'.$inGoingCol;
		$Cb[$outGoingRow] = $dec_vars['V'.$inGoingCol];


		$count = 1;
		while($count <= count($dec_vars)) {
			$const['C'.$outGoingRow]['V'.$count] = round(($const['C'.$outGoingRow]['V'.$count] / $pivotLmnt), 5);
			$count++;
		}
		$RHS['RHS'.$outGoingRow] = round(($RHS['RHS'.$outGoingRow] / $pivotLmnt), 5);

		for($i = 1; $i <= count($const); $i++) {
			$toDivideValue = $const['C'.$i]['V'.$inGoingCol] * -1;
			if($i == $outGoingRow)
				continue;
			$pivotParallel = $const['C'.$i]['V'.$inGoingCol];
			for($j = 1; $j <= count($dec_vars); $j++) {
				$const['C'.$i]['V'.$j] += round(($const['C'.$outGoingRow]['V'.$j] * $toDivideValue), 5);
			}
			$RHS['RHS'.$i] += round(($RHS['RHS'.$outGoingRow] * $toDivideValue), 5);
		}

		//****************************Generation of Zj***************************
		$count = 1;
		while($count <= count($dec_vars)) {
			$sum = 0;
			$j = 1;//indicates #constraints
			//$i = count($dec_vars) - count($const) + 1;
			while($j <= count($const)) {
				$sum += $const['C'.$j]['V'.$count] * $Cb[$j];//$dec_vars['V'.$i];
				$j++;
				//$i++;
			}
			$Zj['Zj'.$count] = $sum;
			$count++;
		}
		//************************End of Generation of Zj************************

		//****************************Generation of Cj - Zj**********************
		$count = 1;
		while($count <= count($dec_vars)) {
			$CjMinusZj['CMZ'.$count] = $dec_vars['V'.$count] - $Zj['Zj'.$count];
			$count++;
		}
		//************************End of Generation of Cj - Zj*******************

		$PivotCol = getMostNegative($CjMinusZj, 'CMZ');
		$count = 1;
		while ($count <= count($const)) {
			if(($const['C'.$count]['V'.$PivotCol] > 0 && $RHS['RHS'.$count] > 0) || ($const['C'.$count]['V'.$PivotCol] < 0 && $RHS['RHS'.$count] < 0))
				$Theta['T'.$count] = $RHS['RHS'.$count] / $const['C'.$count]['V'.$PivotCol];
			else
				$Theta['T'.$count] = Null;
			$count++;
		}


		//*************************************************************************************

		echo '
		<table align="center" id="table">
			<tr>
				<td></td>
				<td></td>';
			$i = 1;
			//*******************Displaying objective coefficients***************
			while($i <= count($dec_vars)) {
				if($dec_vars['V' . $i] == $MaximumVal)
					echo '<td>M</td>';
				else
					echo '<td>' . $dec_vars['V' . $i] . '</td>';
				$i++;
			}
			echo '
						<td></td>
						<td></td>
					</tr>
					<tr>
						<th>Cb</th>
						<th>Xb</th>';
			//****************End of Displaying objective coefficients************
			$i = 1;
			while($i <= count($dec_vars)) {
				echo '<th>X'.$i.'</th>';
				$i++;
			}
			echo '
						<th>b</th>
						<th>Theta</th>
					</tr>';
			$const_num = 1;
			while($const_num <= count($const)) {
				if($Cb[$const_num] == $MaximumVal)
					echo'<tr>
							<td>M</td><td>'.$Xb[$const_num].'</td>';
				else
					echo'<tr>
						<td>'.$Cb[$const_num].'</td><td>'.$Xb[$const_num].'</td>';
				$i = 1;
				while($i <= count($dec_vars)) {
					echo '<td>'.$const['C'.$const_num]['V'.$i].'</td>';
					$i++;
				}
				echo '<td>'.$RHS['RHS'.$const_num].'</td><td>'.$Theta['T'.$const_num].'</td></tr>';
				$basic_loc++;
				$const_num++;
			}
			echo '<tr><td></td><td>Zj</td>';


			//****************************Displaying Zj*******************************
			$count = 1;
			while($count <= count($Zj)) {
				echo '<td>'.$Zj['Zj'.$count].'</td>';
				$count++;
			}
			echo '<td></td><td></td></tr>';
			//************************End of Displaying Zj****************************

			

			//****************************Displaying Cj - Zj**************************
			echo '<tr><td></td><td>Cj - Zj</td>';
			//$i indicates number of decision Variables
			$count = 1;
			while($count <= count($CjMinusZj)) {
				echo '<td>'.$CjMinusZj['CMZ'.$count].'</td>';
				$count++;
			}
			echo '<td></td><td></td></tr>';
			//************************End of Displaying Cj - Zj***********************
			
			echo '
				</table>';

		//*************************************************************************************
	}//End of the while
	}//End of the if(MIN)

	//*************************************************************************************************************
	//*************************************************************************************************************
	//*************************************************************************************************************
	

	//Checking for feasibility
	for($count = 0; $count < count($artificials); $count++)
		if(in_array('X' . $artificials[$count], $Xb) && !isset($exception)) {
			$exception = "Infeasible Region.";
			break;
		}
	//End of checking for feasibility
	if (!checkForFeasibility($CjMinusZj, 'CMZ', $Xb) && !isset($exception)) {
		$exception = 'Infinite Number of Solutions.';
	}

	

	if(!isset($exception)) {
		$sum = 0;
		for($i = 1; $i <= count($RHS); $i++) {
			$sum += $Cb[$i] * $RHS['RHS'.$i];
		}
		echo '<p><div class="optimum">Optimal Solution = '.$sum.'</div></p>';
	}
	else
		echo '<p><div class="optimum">'.$exception.'</div></p>';
	
	} else {
		?>
		<script type="text/javascript">window.location.replace("error.php");</script>
		<?php
	}


	function checkForFeasibility($CMZ, $CMZIN, $XB) {
		for($counter = 1; $counter <= count($XB); $counter++)
			if($CMZ[$CMZIN.$counter] == 0 && !in_array('X'.$counter, $XB))
				return false;
		return true;
	}
	
	function hasPositive($arr, $initializer) {
		$count = 1;
		while($count <= count($arr)) {
			if($arr[$initializer.$count] > 0)
				return true;
			$count++;
		}
		return false;
	}

	function hasNegative($arr, $initializer) {
		$count = 1;
		while($count <= count($arr)) {
			if($arr[$initializer.$count] < 0)
				return true;
			$count++;
		}
		return false;
	}


	function getMostPositive($arr, $initializer) {
		$count = 1;
		$index = $count;
		$max = $arr[$initializer.$count];
		$count++;
		while($count <= count($arr)) {
			if($max < $arr[$initializer.$count]) {
				$max = $arr[$initializer.$count];
				$index = $count;
			}
			$count++;
		}
		return $index;
	}

	function getMostNegative($arr, $initializer) {
		$count = 1;
		$index = $count;
		$min = $arr[$initializer.$count];
		$count++;
		while($count <= count($arr)) {
			if($min > $arr[$initializer.$count]) {
				$min = $arr[$initializer.$count];
				$index = $count;
			}
			$count++;
		}
		return $index;
	}

	function getLeastPositive($arr, $initializer) {
		for($count = 1; $count <= count($arr); $count++) {
			if($arr[$initializer.$count] != Null) {
				$max = $arr[$initializer.$count];
				break;
			}
		}
		$index = $count;
		while($count <= count($arr)) {
			if($max > $arr[$initializer.$count] && $arr[$initializer.$count] > 0) {
				$max = $arr[$initializer.$count];
				$index = $count;
			}
			$count++;
		}
		return $index;
	}
?>
		</div>
	</div>
</div>
</body>
</html>
