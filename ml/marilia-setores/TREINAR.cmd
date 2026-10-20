@echo off
setlocal
cd /d "%~dp0"
if not exist ".venv\Scripts\python.exe" (
  python -m venv .venv
  if errorlevel 1 goto failed
)
".venv\Scripts\python.exe" -m pip install --only-binary=:all: -r requirements.txt
if errorlevel 1 goto failed
if not exist "dados\contagens_diarias.csv" (
  ".venv\Scripts\python.exe" preparar_dados.py
  if errorlevel 1 goto failed
)
".venv\Scripts\python.exe" -m unittest discover -s tests -v
if errorlevel 1 goto failed
".venv\Scripts\python.exe" treino.py --modo completo
if errorlevel 1 goto failed
echo.
echo Concluido. Abra a pasta runs e leia RESULTADO.md na execucao mais recente.
pause
exit /b 0
:failed
echo.
echo A etapa falhou. Preserve a mensagem acima para diagnostico.
pause
exit /b 1
