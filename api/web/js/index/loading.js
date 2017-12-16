var dmId=$("input[name='dmId']").val();
var orderId=$("input[name='orderId']").val();
var type=$("input[name='type']").val();
var token=$("input[name='token']").val();

$(function(){
	var id=setInterval(function(){
		if(type==='accountOpen'){
            $.ajax({
                url:"/index/check-set-password",
                data:{'dmId':dmId},
                dataType:"JSON",
                type:"post",
                success:function(data){
                    clearInterval(id);
                    if(data.retCode==0){
                        location.href='/index/success?token='+token+'&type='+type;
                    }else{
                        location.href="/index/fail?token="+token+'&type='+type;
                    }
                },
                error:function(){
                    clearInterval(id);
                }
            })
        }else if(type==='trusteePay'){
            $.ajax({
                url:"/index/check-trustee-pay",
                data:{'orderId':orderId},
                dataType:"JSON",
                type:"post",
                success:function(data){
                    clearInterval(id);
                    if(data.retCode==0){
                        location.href='/index/success?token='+token+'&type='+type;
                    }else{
                        location.href="/index/fail?token="+token+'&type='+type;
                    }
                },
                error:function(){
                    clearInterval(id);
                }
            })
		}
 	},10000)
})

