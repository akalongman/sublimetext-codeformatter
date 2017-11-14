
format_on_save_scenarios = [
    ({}, '', False),
    ({'format_on_save': True}, '', True),
    ({'format_on_save': False}, '', False),
    ({'format_on_save': '.test$'}, 'file.txt', False),
    ({'format_on_save': '.test$'}, 'file.test', True)
]
