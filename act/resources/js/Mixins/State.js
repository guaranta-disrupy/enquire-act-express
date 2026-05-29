class State {
    constructor() {
      this.criteria = {}
    }
    
    valExists(arr, data) {
      return arr.some(function(el) {
        return el[data.key] == data.value;
      }); 
    }
    deleteSegment(indexes) {
        var str_condtn = 'this.criteria.config.conditions';
        for (let index = 0; index < indexes.length; index++) {
            if ((index + 1) == indexes.length) {
                eval(str_condtn).splice(indexes[index], 1)
            }else {
                str_condtn = str_condtn.concat('['+indexes[index]+'].conditions')
            }
        }
    }
    addSegment(indexes = []) {
        console.log(this.criteria.config.conditions.length);
        if (indexes.length == 0) {
            this.criteria.config.conditions.push({
                method: 'process',
                left_operator: 'and',
                var: '',
                operator: '==',
                val: '',
                conditions: []
            })
        }else {
            var str_condtn = 'this.criteria.config.conditions';
            for (let index = 0; index < indexes.length; index++) {
                str_condtn = str_condtn.concat('['+indexes[index]+'].conditions')
            }
            eval(str_condtn).push({
                method: 'process',
                left_operator: 'and',
                var: '',
                operator: '==',
                val: '',
                conditions: []
            })
        }
    }
    criteriaToString(conditions) {
        var string = ''
        for (const key in conditions) {
            if (key == 0) {
                string += '('
            }else {
                string += ' '+ conditions[key].left_operator.toUpperCase() + ' ('
            }
            if (conditions[key].method == 'process') {
                string += conditions[key].var + conditions[key].operator + conditions[key].val
            }else {
                string += this.criteriaToString(conditions[key].conditions)
            }
            string += ')'
        }
        return string
    }
    fixConfig() {
        if (this.create.config.random.count > this.create.links.length) {
            this.create.config.random.count = this.create.links.length
        }else if(this.create.config.random.count <= 0) {
            this.create.config.random.count = 1
        }
        this.create.config.generateVars.variables.forEach(variable => {
            if (variable.option == 'random_list' || variable.option == 'lowest_list') {
                variable.value = variable.value_string.split(',')
            }else {//remove other values
                if (variable.value[0] < variable.value[1]) {
                    variable.value = [variable.value[0], variable.value[1]]
                }else if(variable.value[0] == variable.value[1]) {
                    variable.value = [variable.value[0], (parseInt(variable.value[1]) + 1)]
                }else {
                    variable.value = [variable.value[1], variable.value[0]]
                }
                
            }
        })
    }
  }
  export default State;
  