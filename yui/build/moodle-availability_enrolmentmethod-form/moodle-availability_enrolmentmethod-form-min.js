YUI.add("moodle-availability_enrolmentmethod-form",function(t,i){M.availability_enrolmentmethod=M.availability_enrolmentmethod||{},M.availability_enrolmentmethod.form=t.Object(M.core_availability.plugin),M.availability_enrolmentmethod.form.enrolmentmethods=null,M.availability_enrolmentmethod.form.initInner=function(i){this.enrolmentmethods=i},M.availability_enrolmentmethod.form.getNode=function(i){var a,e,l,o='<label><span class="pr-3">'+M.util.get_string("title","availability_enrolmentmethod")+'</span> <span class="availability-enrolmentmethod"><select name="id" class="custom-select"><option value="choose">'+M.util.get_string("choosedots","moodle")+'</option>;for(a=0;a<this.enrolmentmethods.length;a++)o+='<option value="'+(e=this.enrolmentmethods[a]).id+'">'+e.name+"</option>";return o+="</select></span></label>",l=t.Node.create('<span class="form-inline">'+o+"</span>"),i.creating===undefined&&(i.id!==undefined&&l.one("select[name=id] > option[value="+i.id+"]")?l.one("select[name=id]").set("value",""+i.id):i.id===undefined&&l.one("select[name=id]").set("value","any")),M.availability_enrolmentmethod.form.addedEvents||(M.availability_enrolmentmethod.form.addedEvents=!0,t.one(".availability-field").delegate("change",function(){M.core_availability.form.update()},".availability_enrolmentmethod select")),l},M.availability_enrolmentmethod.form.fillValue=function(i,a){var e=a.one("select[name=id]").get("value");"choose"===e?i.id="choose":"any"!==e&&(i.id=parseInt(e,10))},M.availability_enrolmentmethod.form.fillErrors=function(i,a){var e={};this.fillValue(e,a),e.id&&"choose"===e.id&&i.push("availability_enrolmentmethod:error_selectenrolmentmethod")}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});