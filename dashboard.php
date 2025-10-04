<?php
$number=$_POST["number"]??null;
function printx($n){
for($i=1;$i<=$n;$i++){
				
for($y=1;$y<=$n;$y++){
//echo "(".$i.",".$y.")";//	echo  "<br>";
				
			
			if($i==$y || ($i+$y) == ($n+1) ){ 
			echo "*";
					
			}
			 else{
			 echo "&nbsp;";
				}
					
				}
				
			  echo  "<br>";
				
			}
}
			$error = "";

					if ($_SERVER["REQUEST_METHOD"] == "POST") {
						
						if (!isset($number) || !is_numeric($number)) {
							$error = "Please enter a valid number.";
						} elseif ($number < 3) {
							$error = "Number must be greater than or equal to 3.";
						} elseif ($number % 2 == 0) {
							$error = "Number must be an odd number.";
						}
					}
					

			?>
			<!DOCTYPE html>
			<html>
			<head> 
			<title> print x shape </title>
			<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
			<script>
			function myfunction() {
					var number = document.getElementById("number").value;
					if(number<=3|| number%2==0) {
						console.log("number must be odd ");
					 alert("Please enter an odd number greater than or equal to 3");
						return false;
						
					 }
					return true;
			}

			</script>
			</head>
			<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg p-4 rounded-4">
                <h2 class="text-center mb-4">Print X Shape</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-danger text-center"><?= $error ?></div>
                <?php endif; ?>

                <form action="" method="POST" onsubmit="return validateForm()">
                    <div class="mb-3">
                        <label for="number" class="form-label">Enter a Number (Odd, ≥3)</label>
                        <input type="number" class="form-control" id="number" name="number" value="<?= htmlspecialchars($number) ?>" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Print X</button>
                    </div>
                </form>

                <?php
                if($number >= 3 && $number % 2 == 1){
                    echo '<hr class="my-4">';
                    echo '<h5 class="text-center mb-3">X Shape for number ' . $number . ':</h5>';
                    printx($number);
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>