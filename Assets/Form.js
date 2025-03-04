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

            if(element.name === element.id && element.name !== '' && element.required && element.value === '')
            {
                requiredCheck = false;
                element.classList.add('invalid');
                
                let helper = $('.helper-text[for='+id+']')[0];
                helper.setAttribute('data-error', 'This field is required.');

                continue;
            }
            
            element.classList.add('valid');
        }
        
        if(!requiredCheck)
        {
            alert('One or more fields are invalid!');
        }
        
        return requiredCheck;
    },
    
    initialize: function(form)
    {
        let elements = $('#'+form.id+' select');
        
        for(let i=0; i<elements.length; i++)
        {
            let element = elements[i];
            let classes = Array.from(element.classList);
            
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
            
            for(let c=0; c<classes.length; c++)
            {
                element.classList.add(classes[c]);
            }
        }
    }
};