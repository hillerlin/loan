var flagNext=true;//下一步
var flagCode=true;//验证码
var mobile;//用户手机号

$(function(){
	
	//点击下一步
	$(".foot .next").on("click",function(){
		if(!flagNext){//避免多次点击
			return false;
		};
		var dmId=$("input[name='dmId']").val();
		var realname=$(".realname").val();
        var token=$("input[name='token']").val();
        var idNo=$(".idNo").val();
		var cardNo=$(".cardNo").val();
		var nameReg=/[\u4e00-\u9fa5]/gm;
		var cardReg=/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/;
		var bankReg=/^([1-9]{1})(\d{14}|\d{18})$/;

		if(!nameReg.test(realname)||!cardReg.test(idNo)||!bankReg.test(cardNo)){
			$(".foot .prompt").text("信息校验有误，请重新验证");
		}else{
			flagNext=false;//防止多次点击下一步发送验证
			//校验信息
			$.ajax({
				url:"/index/check-user-info",
				type:"post",
				data:{"dmId":dmId,"realName":realname,"idNo":idNo+"","cardNo":cardNo},
				dataType:"JSON",
				success:function(data){
					if(data.retCode==0){
						var res=data.retData;
						if(res.setPwd==1) {
                            location.href='/index/success?token='+token+"&type=accountOpen";
                        }else {
                            mobile=res.mobile;//存用户电话号码
                            //把数据存在隐藏form
                            $("input[name='cardNo']").val(cardNo)
                            countDown(true);
                        }
					}else{
						$(".foot .prompt").text("信息校验有误，请重新验证");
					}
					flagNext=true;
				},
				error:function(){
					flagNext=true;
					$(".foot .prompt").text("信息校验有误，请重新验证");
				}
			})
		}
	})
	
	//重发验证码
	$(".mask .code_status").on("click",function(){
		if($(this).text()=="重发"){
			if(!flagCode){
				return false;
			};
			flagCode=false;//不能再次点击
			countDown(false);
		}
	})
	
	//发送验证码后下一步
	$(".check .btn").on("click",function(e){
		var dmId=$("inout[name='dmId']").val();
		var cardNo=$(".cardNo").val();
		var smscode=$(".smscode").val();
		var codeReg=/^\d{6}$/;
		
		$("input[name='cardNo']").val(cardNo);
		$("input[name='smsCode']").val(smscode);
		if(!codeReg.test(smscode)){
			$(".mask .prompt").text("请重新输入验证码");
			e.preventDefault();
		}
		
	})
})


//发送验证码
function countDown(first){
	$.ajax({
		url:"/index/send-sms-code",
		//'srvTxCode'
		data:{"mobile":mobile,'srvTxCode':'accountOpenPlus'},
		type:"post",
		dataType:"JSON",
		success:function(data){
//			if(data.retCode==0){
				if(first){//第一次发送则弹框重发则不弹
					var phone=format(mobile);
					$(".foot .prompt").text("");
					$(".message span").text(phone);
					$(".mask").show();
				}
				var num=60;	
				$(".code_status").text("60s").removeClass("status_color");
				var id=setInterval(function(){
					if(num>0){
						$(".code_status").text(--num+"s")
					}else{
						clearInterval(id);
						$(".code_status").text("重发").addClass("status_color");
					}	
				},1000)
//			}else{
//				if(first){//第一次报错
//					$(".foot .prompt").text("信息校验有误，请重新验证");
//				}else{//重发报错
//					$(".mask .prompt").text(data.retMsg);
//				}
//				
//				
//			}
			flagCode=true;
		},
		error:function(data){
			if(first){
				$(".foot .prompt").text("信息校验有误，请重新验证");
			}else{
				$(".mask .prompt").text(data.retMsg);
			}
			flagCode=true;
		}
		
	})
	
}

//隐藏中间四位电话号码
function format(num){
	var reg = /^(\d{3})\d{4}(\d{4})$/;
	num=num.replace(reg,"$1****$2")
	return num;
}
