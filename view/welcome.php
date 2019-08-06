<?php defined('PASS') or die('unauthorized access!') ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>GENERATE SHORT URL</title>
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style type="text/css">
    	.hidden{display: none;}
    </style>
</head>
<body>

<div style="margin-bottom: 1rem;">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="/">SHORTURL</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav">
                <a class="nav-item nav-link active" href="/">GENERATE <span class="sr-only">(current)</span></a>
                <a class="nav-item nav-link" href="https://github.com/ellermister/shorturl">GITHUB</a>
                <a class="nav-item nav-link" href="https://github.com/ellermister">ABOUT</a>
            </div>
        </div>
    </nav>
</div>

<div class="container">

    <div class="card text-center">
        <div class="card-header">
            GENERATE SHORT URL
        </div>
        <div class="card-body">
            <h5 class="card-title">Quickly generate URL!</h5>

            <!-- <div class="form-group">
                <label for="urlTextInput">URL</label>
                <input type="text" id="urlTextInput" class="form-control" placeholder="Enter URL link">
            </div> -->
			
            <div class="input-group mb-3">
			  <input type="text" id="urlTextInput" class="form-control" placeholder="Enter URL link" >
			  <div class="input-group-append">
				<button class="btn btn-primary" type="button" id="generate" onclick="javascript:generate()">Generate</button>
			  </div>
			</div>
			<div class="mb-3" id="extent-element">
				<div class="custom-control custom-radio custom-control-inline">
				  <input type="radio" id="radio-normal" name="encrypt_type" class="custom-control-input" value="normal">
				  <label class="custom-control-label" for="radio-normal">normal</label>
				</div>
				<div class="custom-control custom-radio custom-control-inline">
				  <input type="radio" id="radio-dynamic" name="encrypt_type" class="custom-control-input" value="dynamic">
				  <label class="custom-control-label" for="radio-dynamic">no referer</label>
				</div>
				<div class="custom-control custom-radio custom-control-inline">
				  <input type="radio" id="radio-encrypt" name="encrypt_type" class="custom-control-input" value="encrypt" checked="">
				  <label class="custom-control-label" for="radio-encrypt">encrypt redirect</label>
				</div>
				<div class="custom-control custom-radio custom-control-inline">
				  <input type="radio" id="radio-once" name="encrypt_type" class="custom-control-input" value="once">
				  <label class="custom-control-label" for="radio-once">redirect once</label>
				</div>
				<div class="custom-control custom-radio custom-control-inline" >
				  <input type="radio" id="radio-password" name="encrypt_type" class="custom-control-input" value="password">
				  <label class="custom-control-label" for="radio-password">password access</label>
				</div>
				<div class="custom-control custom-radio custom-control-inline" style="display: none;">
				  <input type="radio" id="radio-whisper" name="encrypt_type" class="custom-control-input" value="whisper">
				  <label class="custom-control-label" for="radio-whisper">whisper text</label>
				</div>
			</div>
			
			<div class="form-group hidden" extent="radio-password">
			    <label for="input-password">Password</label>
			    <input type="password" class="form-control" id="input-password" placeholder="Password">
		    </div>
			<div class="form-group hidden" extent="radio-whisper">
				<label for="exampleFormControlTextarea1">whisper</label>
				<textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
			</div>
			
            <div id="let-dialog"></div>
            
            <div class="modal" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <h5 class="modal-title">Result</h5>
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          <span aria-hidden="true">&times;</span>
			        </button>
			      </div>
			      <div class="modal-body">
			        <p id="copy-text"><input type="text" name="" id="" value="https://www.baidu.com" readonly="readonly"/></p>
			        <p id="copy-result"></p>
			      </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-primary" onclick="javascript:copy()">Copy</button>
			        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			      </div>
			    </div>
			  </div>
			</div>

        </div>
        <div class="card-footer text-muted">
            No referer access <br> This site generates a total of <?php echo getUrlRecordHistory(); ?>links，Currently active <?php echo getUrlRecord(); ?>。
        </div>
    </div>
</div>


<script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.min.js" ></script>
<script src="https://cdn.bootcss.com/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.bootcss.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script type="text/html" id="tpl-alert">
	<div class="alert alert-{{status}} alert-dismissible fade show" role="alert">
	  <strong>{{status}}!</strong> {{message}}
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
	    <span aria-hidden="true">&times;</span>
	  </button>
	</div>
</script>
<script type="text/javascript">
	function message(msg, status){
		let html = $('#tpl-alert').html();
		html = html.replace(new RegExp('{{message}}', 'g'),msg);
		html = html.replace(new RegExp('{{status}}', 'g'),status);
		$('#let-dialog').html(html);
	}
	
	function copy(){
		$('#copy-text input').select();
		$('#copy-text input').get(0).setSelectionRange(0, $('#copy-text input').val().length);
		if (document.execCommand('copy')) {
	        document.execCommand('copy');
	        $('#copy-result').text('copy success!');
	    }
		
	}
	
	$('#extent-element input[type="radio"]').click(function(){
		var name = $(this).attr('name');
		$('[extent]').hide();
		if($('[name="'+name+'"]:checked').val() == $(this).val()){
			var id = $(this).attr('id');
			$('[extent="'+id+'"]').show();
		}
	});
	
    function generate(){
        let url = $('#urlTextInput').val();
		let encrypt_type = $('input[name="encrypt_type"]:checked').val();
		let extent = $('#input-' + encrypt_type).val();
        $.ajax({
        	type:"post",
        	url:"/api/link",
        	async:true,
        	dataType:'json',
        	data:{url: url, encrypt_type: encrypt_type, extent: extent},
        	success:function(result){
        		if(result.code == 200){
        			$('#copy-text input').val(result.data);
        			message(result.msg, 'success');
        			$('.modal').modal('show');
        		}else{
        			message(result.msg, 'error');
        		}
        	}
        });
    }
</script>
</body>
</html>
