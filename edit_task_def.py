import json
import os

def make_taskdef(input_taskdef, output_taskdef, new_image_url):
    with open(input_taskdef, 'r') as f:
        json_data = json.load(f)

    delete_list = ['registeredAt', 'taskDefinitionArn', 'requiresAttributes', 'revision', 'status', 'placementConstraints', 'compatibilities', 'registeredBy']
    for i in delete_list: 
        json_data['taskDefinition'].pop(i)
    
    #json_data['taskDefinition']['containerDefinitions']['image'] = new_image_url
    #for container in json_data['taskDefinition']['containerDefinitions']:
    #  container['image'] = new_image_url

    with open(output_taskdef,'w') as f:
        json.dump(json_data['taskDefinition'], f, ensure_ascii=False)

        
make_taskdef('origin_task_definition1.json', os.environ['EDITED_TASK_DEF1'], os.environ['NEW_IMAGE_URL'])
#make_taskdef('origin_task_definition2.json', os.environ['EDITED_TASK_DEF2'])