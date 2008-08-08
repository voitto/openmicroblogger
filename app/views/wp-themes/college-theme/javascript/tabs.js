  function display(id)
  {
  var zDD=document.getElementById('show').getElementsByTagName('div');
  var ZON=document.getElementById('name_'+id);
  for(var i=0;i<zDD.length;i++){
      zDD[i].className='hide';    
    }
  ZON.className='show';
  
  var d1=document.getElementById('topTab');
  dArr=d1.getElementsByTagName('a');
  
  for(var i=0;i<dArr.length; i++)
    {
      if(id==(i+1))
        { 
        str=dArr[i].className;
        dArr[i].className=str.replace('_on','')+'_on';
        
        }
          else{ 
          str=dArr[i].className;
          dArr[i].className=str.replace('_on','');  }
      }
  
  
  /*
  if(id==1){ d1.className='cautare_on'; d2.className='contulmeu';}
      else{ d1.className='cautare'; d2.className='contulmeu_on'; }
  */
  }
  
  
  function ShowTab(T)
  {
    i = 0;
    while(document.getElementById("tab" + i) != null){
      document.getElementById("div" + i).style.display = "none";
      document.getElementById("tab" + i).className = "";
      document.getElementById("paging" + i).style.display = "none";
      i++;
    }
    document.getElementById("paging" +  T).style.display = "";
    document.getElementById("div" + T).style.display = "";
    document.getElementById("tab" + T).className = "active";
  }