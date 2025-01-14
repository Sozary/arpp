<?php
/*
 * Controler
 */
// require($nameApp . '/model/user.php');

class mainController
{
    public static function home($request, $context)
    {
        return context::SUCCESS;
    }

    public static function subscribeToEvent($request, $context)
    {
        if (static::checkParameters($request, ['id', 'type', 'name', 'email', 'event_id'])) {
            switch ($request['type']) {
                case 'formation':
                    $event = new Event(Formation::getById($request['id']));
                    break;
                case 'colloquium':
                    $event = new Event(Colloquium::getById($request['id']));
                    break;
                default:
                    echo json_encode(['status' => 403]);
                    return context::NONE;
            }
            $subscription = new Subscription();
            $subscription->email = $request['email'];
            $subscription->name = $request['name'];
            $subscription->event_id = $request['event_id'];
            $subscription->save();

            if ($event->booked_places + 1 > $event->max_places) {
                echo json_encode(['status' => 403]);
                return context::NONE;
            }

            $event->booked_places = $event->booked_places + 1;
            unset($event->type);
            unset($event->id);
            $event->save();

            echo json_encode(['status' => 200]);

        } else {
            echo json_encode(['status' => 403]);
        }
        return context::NONE;
    }

    public static function subscribe($request, $context)
    {
        if (static::checkParameters($request, ['id', 'type'])) {
            switch ($request['type']) {
                case 'formation':
                    $context->payload = Formation::getById($request['id']);
                    break;
                case 'colloquium':
                    $context->payload = Colloquium::getById($request['id']);
                    break;
                default:
                    $context->redirect('?action=home');
                    break;
            }
            if (!$context->payload) {
                $context->redirect('?action=home');
            }
            $context->payload = json_encode($context->payload);
        } else if (static::checkParameters($request, ['type'])) {
            $context->payload = json_encode(['type' => $request['type']]);
        } else {
            $context->redirect('?action=home');
        }
        return context::SUCCESS;
    }

    public static function formations($request, $context)
    {
        return context::SUCCESS;
    }

    public static function colloquia($request, $context)
    {
        return context::SUCCESS;
    }

    public static function deleteEvent($request, $context)
    {
        if ($context->getSessionAttribute('user')) {
            if (isset($request['id']) && isset($request['type'])) {
                switch ($request['type']) {
                    case 'formation':
                        Formation::deleteById($request['id']);
                        break;
                    case 'colloquium':
                        Colloquium::deleteById($request['id']);
                        break;
                    default:
                        echo json_encode(['status' => 403]);
                        break;
                }
                echo json_encode(['status' => 200]);
            } else {
                echo json_encode(['status' => 403]);
            }
        }

        return context::NONE;
    }

    private static function checkParameters($toCheck, $parameters)
    {
        foreach ($parameters as $parameter) {
            if (!isset($toCheck[$parameter])) {
                return false;
            }
            if (!$toCheck[$parameter]) {
                return false;
            }
        }
        return true;
    }

    public static function updateEvent($request, $context)
    {
        if ($context->getSessionAttribute('user')) {
            if (static::checkParameters($request, ['id', 'name', 'type', 'max_places', 'date'])) {
                switch ($request['type']) {
                    case 'formation':
                        $event = new Event(Formation::getById($request['id']));
                        break;
                    case 'colloquium':
                        $event = new Event(Colloquium::getById($request['id']));
                        break;
                    default:
                        echo json_encode(['status' => 403]);
                        return context::NONE;
                }
                $event->name = $request['name'];
                $event->max_places = $request['max_places'];
                $event->date = $request['date'];

                unset($event->type);
                unset($event->id);

                $event->save();

                echo json_encode(['status' => 200]);

            } else if (static::checkParameters($request, ['name', 'type', 'max_places', 'date'])) {
                $event = new Event();
                $event->name = $request['name'];
                $event->date = $request['date'];
                $event->max_places = $request['max_places'];
                $event->booked_places = 0;
                $event_id = $event->save();

                switch ($request['type']) {
                    case 'formation':
                        $formation = new Formation();
                        $formation->event_id = $event_id;
                        $formation->save();
                        break;
                    case 'colloquium':
                        $colloquium = new Colloquium();
                        $colloquium->event_id = $event_id;
                        $colloquium->save();
                        break;
                    default:
                        echo json_encode(['status' => 403]);
                        return context::NONE;
                }

            } else {
                echo json_encode(['status' => 403]);
                return context::NONE;
            }
        } else {
            echo json_encode(['status' => 403]);
            return context::NONE;

        }
        echo json_encode(['status' => 200]);

        return context::NONE;
    }

    public static function editEvent($request, $context)
    {
        if ($context->getSessionAttribute('user')) {
            if (static::checkParameters($request, ['id', 'type'])) {
                switch ($request['type']) {
                    case 'formation':
                        $context->payload = Formation::getById($request['id']);
                        break;
                    case 'colloquium':
                        $context->payload = Colloquium::getById($request['id']);
                        break;
                    default:
                        $context->redirect('?action=admin');
                        break;
                }
                if (!$context->payload) {
                    $context->redirect('?action=admin');
                }
                $context->payload = json_encode($context->payload);
            } else if ($request['type']) {
                $context->payload = json_encode(['type' => $request['type']]);
            } else {
                $context->redirect('?action=admin');
            }
            return context::SUCCESS;
        }
        $context->redirect('?action=login');
    }

    public static function getCurrentUser($request, $context)
    {
        echo json_encode(["status" => 200, "user" => $context->getSessionAttribute('user') ?? null]);
        return context::NONE;
    }

    public static function getFormations($request, $context)
    {
        echo json_encode(["status" => 200, "formations" => Formation::getFormations()]);
        return context::NONE;
    }

    public static function getColloquia($request, $context)
    {
        echo json_encode(["status" => 200, "colloquia" => Colloquium::getColloquia()]);
        return context::NONE;
    }

    public static function disconnect($request, $context)
    {
        $context->setSessionAttribute('user', null);
        echo json_encode(["status" => 200]);
        return context::NONE;
    }

    public static function admin($request, $context)
    {
        if ($context->getSessionAttribute('user')) {
            return context::SUCCESS;
        }
        $context->redirect('?action=login');
    }

    public static function login($request, $context)
    {
        return context::SUCCESS;
    }

    public static function connect($request, $context)
    {
        $user = User::get($request['login'], $request['password']);
        $context->setSessionAttribute('user', $user);
        echo json_encode(["status" => 200, "user" => $user]);

        return context::NONE;
    }

    public static function sendMessage($request, $context)
    {
        // https://grafikart.fr/blog/mail-local-wamp
        mail('mehdiayache@hotmail.fr', 'sujet', $request['content']);
        echo json_encode(["status" => 200]);
        return context::NONE;
    }

    public static function getUsers($request, $context)
    {
        // Petit exemple de comment utiliser enregistrer/récupérer des données
        $user = new User();
        $user->login = "elsa";
        $user->password = "1234";
        $user->age = "22";
        $user->save();

        echo json_encode(["status" => 200, "users" => User::getUsers()]);
        return context::NONE;
    }
    public static function getMovie($request, $context)
    {
        global $nameApp;
        if (isset($request['title'])) {
            $c = json_decode(file_get_contents($nameApp . '/model/data.json'));

            foreach ($c as $v) {
                if ($v->title == $request['title']) {
                    echo json_encode($v);
                    return context::NONE;
                }
            }

        }
        echo "0x0";
        return context::NONE;
    }
//Cherche fichier about success
    public static function about($request, $context)
    {
        return context::SUCCESS;
    }
}
