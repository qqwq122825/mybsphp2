<style type="text/css">
<!--
.STYLE1 {color: #AB4400}
-->
</style>
                <p>&nbsp;</p>
                <p><br/>
                  <br/>
                  
                  
                </p>
                <form action="index.php?m=payment&c=Confirmthepayment" method="post" name="form1" target="_blank" id="form1">
  <label></label>
  <table width="871" height="164" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="87" height="78">&nbsp;</td>
	  <td height="12">
	  <?php


    echo $html;



?></td>
    </tr>
    <tr>
      <td height="84"><div align="right"></div></td>
      <td width="784"><table width="344" border="0">
          <tr>
            <td width="80"><div align="right">金额:</div></td>
            <td width="75"><input name="pay_amount" type="text"  class="perInput1"  id="pay_amount" value="10" size="10" /></td>
            <td width="31">元</td>
            <td width="140"><input type="submit" name="Submit_pay"  id="Submit_pay"   value="立即充值" class="submits" />
            <input name="pay_user" type="hidden" id="pay_user" value="<?php

echo $user_array['user_user'];

?>" /></td>
          </tr>
              </table></td>
    </tr>
  </table>
  <p>&nbsp;</p>
</form>

