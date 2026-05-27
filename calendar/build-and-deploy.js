const { spawn } = require("child_process");

console.log("[NODE] Angular build watcher started...");

const ng = spawn("npx", ["ng", "build", "--watch"], { shell: true });

ng.stdout.on("data", (data) => {
  const output = data.toString();
  process.stdout.write(output);

  if (output.includes("Application bundle generation complete")) {
    console.log("[NODE] Generate mass-definitions.json...");
    
    process.stderr.write("[GENERATE DEFS START]\n");
    const generateDefs = spawn("node", ["scripts/generate-mass-definitions.js"], { shell: true });
    process.stderr.write("[GENERATE DEFS END]\n");
    
    generateDefs.stdout.on("data", (d) => {
      process.stdout.write(`[GENERATE] ${d}`);
    });
    
    generateDefs.stderr.on("data", (d) => {
      process.stderr.write(`[GENERATE ERROR] ${d}`);
    });
    
    generateDefs.on("close", (code) => {
      if (code === 0) {
        console.log("[NODE] Start deploy script...");
        
        const py = spawn("python", ["../docker/miserend/calendar_deploy.py"], { shell: true });
        
        py.stdout.on("data", (d) => {
          process.stdout.write(`[PYTHON STDOUT] ${d}`);
        });

        py.stderr.on("data", (d) => {
          process.stderr.write(`[PYTHON STDERR] ${d}`);
        });

        py.on("close", (code) => {
          console.log(`[NODE] Deploy script ended (exit code: ${code})`);
        });
      } else {
        console.error(`[NODE] Mass definitions generation failed (exit code: ${code})`);
      }
    });
  }

});

ng.stderr.on("data", (data) => {
  process.stderr.write(`[NG STDERR] ${data}`);
});

ng.on("close", (code) => {
  console.log(`[NODE] Angular watcher leállt (exit code: ${code})`);
});
