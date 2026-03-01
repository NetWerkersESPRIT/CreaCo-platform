import re
path=r'c:\\CreaCop\\templates\\auth\\visitor.html.twig'
with open(path,'r',encoding='utf-8') as f:
    lines=f.readlines()
start=None; end=None
for i,l in enumerate(lines):
    if '{% if random_courses' in l:
        start=i
    if start is not None and '{% endif %}' in l and end is None and i>start:
        end=i
        break
if start is None or end is None:
    print('could not find block')
    import sys; sys.exit(1)
new_block = """{% if random_courses is defined and random_courses|length > 0 %}
                                                              <div class=\"mt-12 container mx-auto px-6\"> 
                                                                    <h3 class=\"text-2xl font-bold text-slate-800 mb-6\">Selected Courses</h3>
                                                                    <div class=\"flex flex-wrap -mx-3\"> 
                                                                          {% for course in random_courses %}
                                                                                <div class=\"w-full max-w-full px-3 mb-6 sm:w-1/2 lg:w-1/3\"> 
                                                                                      <div class=\"relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border h-full hover:shadow-soft-2xl transition-all duration-300 group\"> 
                                                                                            <div class=\"relative h-48 overflow-hidden rounded-t-2xl\"> 
                                                                                                  {% if course.image %} 
                                                                                                        <img src=\"{{ course.image }}\" alt=\"{{ course.titre }}\" class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-500\"> 
                                                                                                  {% else %} 
                                                                                                        <div class=\"w-full h-full flex items-center justify-center bg-gradient-to-tl from-gray-900 to-slate-800 text-white\"> 
                                                                                                              <i class=\"fas fa-graduation-cap text-4xl\"></i> 
                                                                                                        </div> 
                                                                                                  {% endif %} 
                                                                                                  <div class=\"absolute top-0 right-0 m-4 px-2 py-1 rounded bg-white shadow-soft-2xl\"> 
                                                                                                        <span class=\"text-xs font-bold text-slate-700\">{{ course.ressources|length }} 
                                                                                                              <i class=\"fas fa-file-alt ml-1\"></i> 
                                                                                                        </span> 
                                                                                                  </div> 
                                                                                            </div> 
                                                                                            <div class=\"flex-auto p-6\"> 
                                                                                                  <h5 class=\"mb-2 font-bold\">{{ course.titre }}</h5> 
                                                                                                  <p class=\"mb-4 text-sm text-slate-500 leading-normal line-clamp-3\">{{ course.description }}</p> 
                                                                                                  <div class=\"flex items-center justify-between\"> 
                                                                                                        <span class=\"text-xs font-semibold text-slate-400\"> 
                                                                                                              <i class=\"fas fa-calendar-alt mr-1\"></i> 
                                                                                                              {{ course.dateDeCreation ? course.dateDeCreation|date('d/m/Y') : '-' }} 
                                                                                                        </span> 
                                                                                                        <a href=\"{{ path('app_front_course', {'id': course.id}) }}\" class=\"inline-block px-4 py-2 font-bold text-center text-white uppercase align-middle transition-all bg-transparent border-0 rounded-lg cursor-pointer leading-pro text-xs ease-soft-in shadow-soft-md bg-150 bg-x-25 bg-gradient-to-tl from-purple-700 to-pink-500 hover:scale-102 active:opacity-85 hover:shadow-soft-xs tracking-tight-rem\"> 
                                                                                                              Access 
                                                                                                        </a> 
                                                                                                  </div> 
                                                                                            </div> 
                                                                                      </div> 
                                                                                </div> 
                                                                          {% endfor %} 
                                                                    </div> 
                                                              </div> 
                                                        {% endif %}"""
# replace lines
lines[start:end+1] = [ln+"\n" for ln in new_block.splitlines()]
with open(path,'w',encoding='utf-8') as f:
    f.writelines(lines)
print('replaced', start, 'to', end)
