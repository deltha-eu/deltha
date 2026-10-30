-- migra-175 (30/10/2026): vectores de titulares (lib/vectores.php). «¿Es la misma noticia?» tiene que responder al momento y el modelo
-- del PC no puede (pide trabajo cada pocos segundos y tarda otros tantos en contestar), asi que la comparacion de titulares pasa a un
-- servicio pequeño en el propio droplet (scripts/vectores/servidor.py: modelo multilingüe de frases, ONNX en CPU, decenas de ms).
-- Cada envio guarda su vector; al enviar se comparan por coseno en PHP. La tarea «relacionadas» de la IA (migra-173) desaparece:
-- story_related lo rellenan ahora los vectores al publicar. Pedido por Angel. Idempotente.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS story_vectors (
  story_id INT UNSIGNED NOT NULL,
  dim      SMALLINT UNSIGNED NOT NULL,
  vec      BLOB NOT NULL COMMENT 'float32 little-endian, normalizado (pack g*)',
  model    VARCHAR(80) DEFAULT NULL,
  updated  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (story_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO config (ckey, cvalue, descr) VALUES
('vectores_on',            '1',                     '1 = se usan los vectores de titulares (servicio scripts/vectores en el droplet) para «¿es la misma noticia?» al enviar y las relacionadas del hilo; 0 = solo el filtro de palabras'),
('vectores_url',           'http://127.0.0.1:8090', 'Donde escucha el servicio de vectores (scripts/vectores/servidor.py)'),
('vectores_timeout_ms',    '300',                   'Milisegundos que se espera al servicio de vectores al enviar; pasados, se cae al filtro de palabras y el envio sigue'),
('vectores_dias',          '90',                    'Hasta que antiguedad (dias) se comparan los envios al buscar parecidas y relacionadas'),
('parecidas_vector_min',   '0.75',                  'Parecido minimo (coseno, 0-1) para avisar en /enviar de que ya hay una noticia igual; subir si avisa de mas, bajar si se le escapan'),
('relacionadas_vector_min','0.65',                  'Parecido minimo (coseno, 0-1) para que un envio salga en «Relacionadas» del hilo de otro'),
('parecidas_min_comunes',  '3',                     'Respaldo sin servicio de vectores: palabras con sustancia del titular que tienen que coincidir para avisar en /enviar'),
('parecidas_min',          '0.5',                   'Respaldo sin servicio de vectores: fraccion minima de palabras del titular mas corto que tienen que coincidir');

-- la tarea «relacionadas» de la IA del PC se retira (lo hacen los vectores); su config y story_related se quedan
DELETE FROM ia_jobs  WHERE task_key = 'relacionadas';
DELETE FROM ia_tasks WHERE task_key = 'relacionadas';
DELETE FROM config WHERE ckey IN ('relacionadas_min', 'relacionadas_dias');
