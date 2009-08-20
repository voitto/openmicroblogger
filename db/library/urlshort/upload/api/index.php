<?php include '../includes/header-one.php'; ?>
	
	<body>
		
<?php include '../includes/header-two.php'; ?>
		
		<p><form>
		<h2>API - <a href="<?php include 'install_path.php'; echo $install_path; ?>api.php">api.php</a></h2>
<br/>You can use our API file to create a shortened URL with our service, or look up the full length URL via a simple URL request.<br/>
<h3>Examples</h3>
<div class="example">
<a href="<?php include 'install_path.php'; echo $install_path; ?>api.php?url=http://urlshort.sourceforge.net/download"><?php include 'install_path.php'; echo $install_path; ?>api.php?url=http://urlshort.sourceforge.net/download</a><br/>
<br/><a href="<?php include 'install_path.php'; echo $install_path; ?>api.php?short=<?php include 'install_path.php'; echo $install_path; ?>1"><?php include 'install_path.php'; echo $install_path; ?>api.php?short=<?php include 'install_path.php'; echo $install_path; ?>1</a><br/>
</div><br/>For more info visit <a href="../example.php">example.php</a>.<br/>

		</form></p>

<?php include '../includes/footer.php'; ?>