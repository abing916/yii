<?php

/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
<script src="/assets/a7dc7609/jquery.js"></script>
<div>
    <select class="language" name="language" id="language">
        <option value="en" <?php echo Yii::$app->session->get('lang') == 'en' ? 'selected':''; ?>>English</option>
        <option value="ch"  <?php echo Yii::$app->session->get('lang') == 'ch' ? 'selected':''; ?>>Chinese</option>
    </select>
    <span><?php echo $message ?></span>
</div>

<script>
    $(".language").on('change',function () {
        var lang = $(this).val()

        $.ajax({
            type: "POST",
            async: false,
            url: '/test/ajax',
            data: {lang:lang},
            dataType:'json',
            success: function(data){

            },
            error:function(){
                return false;
            }
        });
    })
</script>