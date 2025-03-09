var Form =
{
    validate: function(form)
    {
        let elements = $('#'+form.id+' input, #'+form.id+' select');
        let requiredCheck = true;
        
        for(let i=0; i<elements.length; i++)
        {
            let element = elements[i];
            let id = element.id;
            element.removeAttribute('data-error');
            element.classList.remove('valid');
            element.classList.remove('invalid');
            
            if(element.tagName === 'SELECT')
            {
                let parent = element.parentElement;
                
                for(let c=0; c<parent.children.length; c++)
                {
                    let child = parent.children[c];
                    if(child.tagName === 'INPUT')
                    {
                        child.classList.remove('valid');
                        child.classList.remove('invalid');
                        element = child;
                    };
                }
            }
            
            if(id === '')
            {
                continue;
            }

            let helper = $('.helper-text[for='+id+']')[0];
            if(element.name === element.id && element.required && element.value === '')
            {
                requiredCheck = false;
                element.classList.add('invalid');
                helper.setAttribute('data-error', 'This field is required.');

                continue;
            }
            
            element.classList.add('valid');
            helper.setAttribute('data-success', 'OK.');
        }
        
        if(!requiredCheck)
        {
            alert('One or more fields are invalid!');
        }
        
        return requiredCheck;
    },
    
    initialize: function(form)
    {
    }
};