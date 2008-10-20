<?php
  
  // post a notice from wp-comment form

  $redir = 'http://'.$_SERVER['SERVER_NAME']."/?posts";

?>

<html>

<body bgcolor="#000000">

<form name="myForm" method="post" action="<?php echo $redir; ?>">
  <input type="hidden" name="method" value="post" />
  <input type="hidden" name="post[title]" value="<?php echo $_POST['comment']; ?>" />
  <input type="hidden" name="post[local]" value="1" />
  <input type="hidden" name="post[parent_id]" value="<?php echo $_POST['comment_post_ID']; ?>" />
  
</form>

</body>

<script type="text/javascript" language="javascript">
document.myForm.submit();
</script>
             
             
</html>