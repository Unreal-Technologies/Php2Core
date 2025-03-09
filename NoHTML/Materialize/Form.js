Math.decimals = function(number)
{
    let parts = number.toString().split('.');
    
    if(parts.length === 1)
    {
        return 0;
    }
    
    return parts[1].length;
};

Math.roundFloat = function(number, decimals)
{
    let mul = Math.pow(10, decimals);
    return Math.round(Math.round(number * mul), 10) / mul;
};

class Form
{
    static validate(form)
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
    };
    
    static initialize(form)
    {
        Form.#overrideNumbers(form);
    };
    
    static #overrideNumbers_event(target, state)
    {
        let min = target.getAttribute('min');
        let max = target.getAttribute('max');
        let step = target.getAttribute('step');
        let value = target.value;

        if(step === null)
        {
            step = 1;
        }

        if(min === null)
        {
            min = Number.MIN_SAFE_INTEGER;
        }

        if(max === null)
        {
            max = Number.MAX_SAFE_INTEGER;
        }

        let decimals = Math.decimals(step);
        let isInt = parseFloat(step, 10) === parseInt(step, 10);
        let number = Math.roundFloat(isInt ? parseInt(value, 10) : parseFloat(value, 10), decimals);

        if(state === 1)
        {
            number += isInt? parseInt(step, 10) : parseFloat(step, 10);
        }
        if(state === -1)
        {
            number -= isInt? parseInt(step, 10) : parseFloat(step, 10);
        }

        if(number < min)
        {
            number = min;
        }

        if(number > max)
        {
            number = max;
        }

        target.value = Math.roundFloat(number, decimals);
    };
    
    static #overrideNumbers(form)
    {
        let elements = $('#'+form.id+' input[type=number]');
        
        for(let i=0; i<elements.length; i++)
        {
            let element = elements[i];
            element.type = 'text';
            element.onchange = function(event)
            {
                Form.#overrideNumbers_event(event.target, 0);
            };
            
            let rect = element.getBoundingClientRect();

            let up = document.createElement('i');
            up.setAttribute('class', 'fas fa-caret-up nud');
            up.onmousedown = function()
            {
                Form.#overrideNumbers_event(element, 1);
            };
            
            let down = document.createElement('i');
            down.setAttribute('class', 'fas fa-caret-down nud');
            down.onmousedown = function()
            {
                Form.#overrideNumbers_event(element, -1);
            };

            form.appendChild(up);
            form.appendChild(down);

            let upRect = up.getBoundingClientRect();
            up.style.left = (rect.left + rect.width - upRect.width)+'px';
            up.style.top = (rect.top)+'px';
            
            down.style.left = (rect.left + rect.width - upRect.width)+'px';
            down.style.top = (rect.top + upRect.height + 4)+'px';
        }
    };
};