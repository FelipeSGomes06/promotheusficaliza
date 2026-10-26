"""Lista funções e classes nomeadas dos arquivos Python recebidos (pelo módulo ast; não executa nada).

Uso: python -I scripts/documentacao/simbolos-py.py ml/marilia-setores/treino.py …
Saída: uma linha JSON por símbolo, lida por inventario.mjs.
"""

import ast
import json
import sys


def walk(node, file, prefix):
    for child in ast.iter_child_nodes(node):
        if isinstance(child, (ast.FunctionDef, ast.AsyncFunctionDef, ast.ClassDef)):
            name = f"{prefix}.{child.name}" if prefix else child.name
            if isinstance(child, ast.ClassDef):
                kind, signature = "class", child.name
            else:
                kind = "method" if prefix and isinstance(node, ast.ClassDef) else "function"
                signature = f"{child.name}({ast.unparse(child.args)})" + (f" -> {ast.unparse(child.returns)}" if child.returns else "")
            print(json.dumps({"file": file, "kind": kind, "name": name, "short": child.name, "signature": signature,
                              "visibility": "privada" if child.name.startswith("_") else "módulo", "line": child.lineno}, ensure_ascii=False))
            walk(child, file, name)
        else:
            walk(child, file, prefix)


sys.stdout.reconfigure(encoding="utf-8")
for path in sys.argv[1:]:
    with open(path, encoding="utf-8") as handle:
        walk(ast.parse(handle.read(), path), path, "")
