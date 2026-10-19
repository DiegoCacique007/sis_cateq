<?php

namespace App\Services\Chatbot\Intent;

use App\Enums\ChatbotIntent as I;

final class IntentPatternCatalog
{
    /** Prioridad local: modificación 30, orientación 20, consulta 10, ayuda 0.
     * @return array<string, array{intent: I, priority: int, pattern: string}>
     */
    public function patterns(): array
    {
        $modify = '(?:cambiar|cambia|modificar|modifica|editar|edita|actualizar|actualiza|corregir|corrige)';
        $register = '(?:registrar|registro|agregar|agrego|dar de alta|doy de alta|capturar|capturo)';
        $how = '(?:(?:como|donde)(?: puedo| se| debo)?|ayuda para|ayudame a)';
        $read = '(?:ver|veo|consultar|consulto|muestrame|mostrar|ensename)';
        $manage = '(?:administrar|administro|gestionar|gestiono|manejar|manejo|crear|creo|registrar|registro|agregar|agrego)';
        // Reporte de problema («no me aparecen…», «no encuentro…»): se atiende como la consulta.
        $report = '(?:aparece|aparecen|sale|salen|carga|cargan|muestra|muestran|encuentro|localizo)';
        $gap = '(?:\s+(?:un|una|el|la|los|las|mi|mis|este|esta|de|del|datos|informacion))*\s+';
        $rules = [
            'MOD-STUDENT' => [I::ModifyStudent, 30, "$modify$gap(?:alumno|alumna|alumnos|alumnas)\\b"],
            'MOD-ADMIN' => [I::ModifyAdministrativeData, 30, "$modify$gap(?:administrativ[oa]s?|tutores?|comunidades?|periodos?|niveles?|unidades?|rubros?|usuarios?|grupos?)\\b"],
            'HOW-EVALUATIONS' => [I::HowToRecordEvaluations, 20, "$how\\s+(?:(?:registrar|registro|capturar|capturo|poner|pongo)$gap(?:calificaciones?|evaluaciones?|calificacion|evaluacion)|evaluar\\b)|\\b(?:no\\s+(?:puedo|podemos|logro|me deja|me dejan|me permite)|puedo|podemos|se puede|se pueden)\\s+(?:registrar|capturar|guardar|poner|subir|cargar|meter|ingresar)$gap(?:calificaciones?|evaluaciones?|calificacion|evaluacion)\\b|\\bno\\s+(?:me deja|me dejan|me permite)\\s+que\\s+(?:registre|capture|guarde|ponga|suba|cargue|meta)$gap(?:calificaciones?|evaluaciones?|calificacion|evaluacion)\\b|\\bno\\s+se\\s+(?:guarda|guardan)$gap(?:calificaciones?|evaluaciones?|calificacion|evaluacion)\\b"],
            'HOW-STUDENT' => [I::HowToRegisterStudent, 20, "$how\\s+$register(?:\\s+al?)?$gap(?:alumnos?|alumnas?)\\b|\\bdar de alta(?:\\s+al?)?$gap(?:alumnos?|alumnas?)\\b|\\b(?:puedo|podemos|logro|deja|dejan|permite)\\s+$register(?:\\s+al?)?$gap(?:alumnos?|alumnas?)\\b"],
            'HOW-TUTOR' => [I::HowToRegisterTutor, 20, "(?:$how\\s+)?(?:registrar|registro|agregar|agrego)$gap(?:tutor|tutores|tutora|tutoras)\\b"],
            'HOW-INSCRIPTION' => [I::HowToRegisterInscription, 20, "(?:$how\\s+)?(?:inscribir|inscribo)(?:\\s+al?)?$gap(?:alumnos?|alumnas?)\\b|(?:$how\\s+)?(?:registrar|registro|hacer|hago)$gap(?:inscripcion|inscripciones)\\b"],
            'HOW-ASSIGN' => [I::HowToAssignGroup, 20, "$how\\s+(?:asignar|asigno)$gap(?:catequistas?|grupos?)\\b"],
            'VIEW-GROUPS' => [I::ViewMyGroups, 10, "\\bmis grupos\\b|\\bque grupos tengo\\b|\\bgrupos? asignados?\\b|(?:$read|$report)$gap(?:grupo|grupos)\\b|\\bno tengo(?:\\s+ningun[oa]?s?)?$gap(?:grupo|grupos)\\b"],
            'VIEW-STUDENTS' => [I::ViewGroupStudents, 10, "(?<!de )\\bmis alumnos\\b|\\balumnos de mi grupo\\b|(?:$read|$report)$gap(?:alumnos?|alumnas?)\\b|\\blista de alumnos\\b|\\bno tengo(?:\\s+ningun[oa]?s?)?$gap(?:alumnos?|alumnas?)\\b"],
            'VIEW-ATTENDANCE' => [I::ViewAttendanceList, 10, '\b(?:lista|pdf) de asistencia\b|\b(?:consultar|consulto|generar|genera)\s+(?:(?:mi|la)\s+)?asistencia\b'],
            'VIEW-EVALUATIONS' => [I::ViewEvaluations, 10, "(?:$read|$report)$gap(?:calificaciones|evaluaciones|calificacion|evaluacion)\\b|\\bcalificaciones de mis alumnos\\b"],
            'VIEW-BOLETA' => [I::ViewBoleta, 10, "(?:$read|generar|genera|descargar)$gap(?:boleta|boletas)\\b"],
            'HELP' => [I::HelpSystem, 0, '^ayuda$|\bque puedes hacer\b|\bcomo funciona el sistema\b|\bque puedo consultar\b'],
        ];
        foreach ([
            'COMMUNITIES' => [I::HowToManageCommunities, 'comunidad|comunidades'],
            'PERIODS' => [I::HowToManagePeriods, 'periodo|periodos'],
            'LEVELS' => [I::HowToManageLevels, 'nivel|niveles'],
            'UNITS' => [I::HowToManageUnits, 'unidad|unidades'],
            'RUBRICS' => [I::HowToManageRubrics, 'rubro|rubros'],
            'USERS' => [I::HowToManageUsers, 'usuario|usuarios'],
        ] as $id => [$intent, $subject]) {
            $rules['HOW-'.$id] = [$intent, 20, "$how\\s+$manage$gap(?:$subject)\\b"];
        }

        $catalog = [];
        foreach ($rules as $id => [$intent, $priority, $pattern]) {
            $catalog[$id] = ['intent' => $intent, 'priority' => $priority, 'pattern' => '~\b(?:'.$pattern.')~u'];
        }

        return $catalog;
    }

    /** Bloqueos conservadores: no convertir ejecución inexistente o negación en consulta.
     * «no sé/entiendo/recuerdo cómo/dónde» expresa desconocimiento y «no puedo/me deja/me aparecen/encuentro…»
     * reporta un problema, también impersonal («no se ven/guardan…») o de carencia («no tengo grupo/alumnos»);
     * ninguno es negación. Cualquier otro «no» sigue bloqueando.
     */
    public function guards(): array
    {
        // Escritura de calificaciones: infinitivo, 1.ª persona, tú, él, ellos, usted(es) y enclíticos.
        // Se excluyen las formas que son sustantivos («cargo», «asiento», «ingreso»).
        $gradeWrite = '(?:registra|registrar|registro|registran|registras|registre|registren|registrala|registralas|registralo|registralos|registrarla|registrarlas|registrarlo|registrarlos'
            .'|captura|capturar|capturo|capturan|capturas|capture|capturen|capturala|capturalas|capturalo|capturalos|capturarla|capturarlas|capturarlo|capturarlos'
            .'|guarda|guardar|guardo|guardan|guardas|guarde|guarden|guardala|guardalas|guardalo|guardalos|guardarla|guardarlas|guardarlo|guardarlos'
            .'|pon|poner|pongo|pone|ponen|pones|ponga|pongan|ponla|ponlas|ponlo|ponlos|ponle|ponles|ponerla|ponerlas|ponerlo|ponerlos|ponerle|ponerles'
            .'|mete|meter|meto|meten|metes|meta|metan|metela|metelas|metelo|metelos|metele|meteles|meterla|meterlas|meterlo|meterlos|meterle|meterles'
            .'|sube|subir|subo|suben|subes|suba|suban|subela|subelas|subelo|subelos|subele|subeles|subirla|subirlas|subirlo|subirlos|subirle|subirles'
            .'|carga|cargar|cargan|cargas|cargue|carguen|cargala|cargalas|cargalo|cargalos|cargale|cargales|cargarla|cargarlas|cargarlo|cargarlos|cargarle|cargarles'
            .'|anota|anotar|anoto|anotan|anotas|anote|anoten|anotala|anotalas|anotalo|anotalos|anotale|anotales|anotarla|anotarlas|anotarlo|anotarlos|anotarle|anotarles'
            .'|asienta|asentar|asientan|asientas|asiente|asienten|asientala|asientalas|asentarla|asentarlas'
            .'|califica|calificar|califico|califican|calificas|califique|califiquen|calificala|calificalas|calificalo|calificalos|calificale|calificales|calificarla|calificarlas|calificarlo|calificarlos|calificarle|calificarles'
            .'|ingresa|ingresar|ingresan|ingresas|ingrese|ingresen|ingresala|ingresalas|ingresalo|ingresalos|ingresarla|ingresarlas|ingresarlo|ingresarlos'
            .'|introduce|introducir|introduzco|introducen|introduces|introduzca|introduzcan|introducela|introducelas|introducirla|introducirlas|introducirlo|introducirlos)';
        // Un reporte o pregunta propia («no me deja (que)…», «puedo…», «se puede…», «dónde…») solo exime al
        // verbo que le sigue inmediatamente; «puedes/podrías» (pedir al chatbot) sigue bloqueando.
        // Del impersonal solo se exime «no se guarda(n)»; «carga(n)» tras «me/se/la» es consulta o sustantivo.
        // Escritura de asistencia: sin «cargar» (consulta) ni los sustantivos «marco», «tomo», «paso».
        $attendanceWrite = '(?:registra|registrar|registro|registran|registras|registre|registren|registrala|registralas'
            .'|captura|capturar|capturo|capturan|capturas|capture|capturen|capturala|capturalas'
            .'|guarda|guardar|guardo|guardan|guardas|guarde|guarden|guardala|guardalas'
            .'|marca|marcar|marcan|marcas|marque|marquen|marcala|marcalas'
            .'|toma|tomar|toman|tomas|tome|tomen|tomala|tomalas'
            .'|pasa|pasar|pasan|pasas|pase|pasen|pasala|pasalas'
            .'|pon|poner|pongo|pone|ponen|pones|ponga|pongan|ponle|ponla|ponlas'
            .'|anota|anotar|anoto|anotan|anotas|anote|anoten|anotala|anotalas)';
        $gradeExempt = '(?<!puedo |podemos |no logro |no me deja |no me dejan |no me permite |no me deja que |no me dejan que |no me permite que |se puede |se pueden |donde )'
            .'(?!(?<=no se )guardan?\b)(?!(?<=me |se |la )cargan?\b)';

        return [
            'NEGATED-REQUEST' => '~\b(?:nunca|tampoco)\b|\bno\b(?!\s+(?:se|sabemos|entiendo|recuerdo)\s+(?:como|donde)\b)'
                .'(?!\s+(?:puedo|podemos|logro|me\s+deja|me\s+dejan|me\s+permite|(?:me\s+)?(?:aparece|aparecen|sale|salen|carga|cargan|muestra|muestran)|encuentro|veo'
                .'|se\s+(?:ve|ven|guarda|guardan|carga|cargan|muestra|muestran)'
                .'|tengo(?:\s+(?:un|una|el|la|los|las|mi|mis|ningun|ninguna|ningunos|ningunas))*\s+(?:grupos?|alumnos?|alumnas?))\b)~u',
            'UNSUPPORTED-ATTENDANCE' => '~\b'.$attendanceWrite.'\b.*\basistencia\b~u',
            'UNSUPPORTED-EVALUATION-WRITE' => '~^(?!.*\b(?:como|ayuda|ayudame)\b).*'.$gradeExempt.'\b'.$gradeWrite.'\b.*\b(?:calificacion|calificaciones|evaluacion|evaluaciones)\b~u',
        ];
    }
}
