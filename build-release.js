//@ts-check
import path from 'node:path';
import process from 'node:process';
import fs from 'node:fs/promises';
import util from 'node:util';
import { exec } from 'node:child_process';

/**
 * @type {Array<NodeJS.Platform>}
 */
const OSs = ['linux', 'darwin'];

if (!OSs.includes(process.platform)) {
    coloredLog(
        '\nBy the moment, this script is compatible only with linux and MacOS systems.\n',
        'yellow'
    );
    console.log('Closing...');
    process.exit(0);
}
const cwd = process.cwd();
(async () => {
    const pkgString = await fs.readFile(path.resolve(cwd, './package.json'), {
        encoding: 'utf8'
    });
    const pkg = JSON.parse(pkgString);
    const dirName = `${pkg.name}`;
    const fileName = `${pkg.name}-v${pkg.version}.zip`;
    const inPath = path.resolve(cwd, './src'); // folder to zip
    const releaseFolder = path.resolve(cwd, `./release`);
    const outPathTemp = path.resolve(releaseFolder, `./${dirName}`); // name of output zip file
    const outPath = path.resolve(releaseFolder, `./${fileName}`); // name of output zip file
    const mainPluginFile = 'wpr-diagnoser.php';
    const mainPluginFilePath = path.resolve(outPathTemp, `./${mainPluginFile}`);
    console.log('\n');
    try {
        const result = await fs.access(
            inPath,
            fs.constants.R_OK | fs.constants.W_OK
        );
    } catch (e) {
        const errorName = util.getSystemErrorName(e.errno);
        if (errorName === 'ENOENT') {
            console.error(
                colorizeText(
                    "'src' directory does not exist, make sure the project is not broken",
                    'red'
                )
            );
            process.exit(1);
        } else {
            console.error(
                colorizeText(
                    "Something went wrong reading the 'src' directory\n",
                    'red'
                ),
                e
            );
            process.exit(1);
        }
    }
    try {
        const result = await fs.access(
            releaseFolder,
            fs.constants.R_OK | fs.constants.W_OK
        );
    } catch (e) {
        const errorName = util.getSystemErrorName(e.errno);
        if (errorName === 'ENOENT') {
            try {
                coloredLog(`Creating directory: ${releaseFolder}`, 'blue');
                await fs.mkdir(releaseFolder);
            } catch (e) {
                console.error(
                    colorizeText(
                        `Could not create directory: ${releaseFolder}\n`,
                        'red'
                    ),
                    e
                );
                process.exit(1);
            }
        } else {
            console.error(
                colorizeText(
                    `Something went wrong reading path: ${releaseFolder}\n`,
                    'red'
                ),
                e
            );
            process.exit(1);
        }
    }
    // process to create the temp folder
    let result = null;
    try {
        result = await fs.access(
            outPathTemp,
            fs.constants.R_OK | fs.constants.W_OK
        );
    } catch (e) {
        if (util.getSystemErrorName(e.errno) !== 'ENOENT') {
            console.error(
                colorizeText(
                    `Something went wrong with the path: ${outPathTemp}\n`,
                    'red'
                ),
                e
            );
            process.exit(1);
        }
    }
    if (result === undefined) {
        await removeOutPathTemp(outPathTemp);
    }
    try {
        coloredLog(`Creating directory: ${outPathTemp}`, 'blue');
        await fs.mkdir(outPathTemp);
    } catch (e) {
        console.error(
            colorizeText(`Could not create directory: ${outPathTemp}\n`, 'red'),
            e
        );
        process.exit(1);
    }
    const command = `cp -R ${inPath}/* ${outPathTemp}`;
    coloredLog(`Copying files to: ${outPathTemp}...\n`, 'blue');
    exec(command, async (error, stdout, stderr) => {
        if (error) {
            console.error(
                colorizeText(
                    `Something when wrong copying the content:\nFrom: ${inPath}/* \nTo: ${outPathTemp}`,
                    'red'
                ),
                error
            );
            process.exit(1);
        }
        if (stdout) {
            console.log(stdout, '\n');
        }
        if (stderr) {
            console.log(stderr);
        }
        coloredLog(`Writing version: ${pkg.version}...\n`, 'blue');
        await replaceVersioInFiles(
            pkg.version,
            mainPluginFile,
            mainPluginFilePath,
            outPathTemp
        );
        processZip(outPathTemp, outPath, fileName, dirName, releaseFolder);
    });
})();
/**
 * @param {string} outPath
 * @param {string} fileName
 * @param {string} outPathTemp
 * @param {string} dirName
 * @param {string} releaseFolder
 *
 */
async function processZip(
    outPathTemp,
    outPath,
    fileName,
    dirName,
    releaseFolder
) {
    // process to create the zip
    let result = null;
    try {
        result = await fs.access(
            outPath,
            fs.constants.R_OK | fs.constants.W_OK
        );
    } catch (e) {
        if (util.getSystemErrorName(e.errno) !== 'ENOENT') {
            console.error(
                colorizeText(
                    `Something went wrong with the path: ${outPath}\n`,
                    'red'
                ),
                e
            );
            process.exit(1);
        }
    }
    if (result === undefined) {
        try {
            coloredLog(`Removing file: ${outPath}`, 'blue');
            await fs.unlink(outPath);
        } catch (e) {
            console.error(
                colorizeText(
                    `Something went wrong while removing the file: ${outPath}\n`,
                    'red'
                ),
                e
            );
            process.exit(1);
        }
    }
    const command = `cd ${releaseFolder} && zip -r ${fileName} ${dirName} && cd ${cwd}`;
    coloredLog(`Packing file: ${fileName}...\n`, 'blue');
    exec(command, async (error, stdout, stderr) => {
        if (error) {
            console.error(
                colorizeText(
                    'Something when wrong running the zip command:\n',
                    'red'
                ),
                error
            );
            process.exit(1);
        }
        if (stdout) {
            console.log(stdout);
        }
        if (stderr) {
            console.log(stderr);
        }
        // await removeOutPathTemp(outPathTemp);
        console.log('');
        coloredLog('Process finished successfuly...\n', 'green');
        coloredLog(`Find the packaged file in ${outPath}\n`, 'green');
    });
}
/**
 * @param {string} outPathTemp
 */
async function removeOutPathTemp(outPathTemp) {
    try {
        coloredLog(`Removing temporary directory: ${outPathTemp}`, 'blue');
        await fs.rm(outPathTemp, { recursive: true, force: true });
    } catch (e) {
        console.error(
            colorizeText(
                `Something went wrong while removing the directory: ${outPathTemp}\n`,
                'red'
            ),
            e
        );
        process.exit(1);
    }
}
const colorSet = {
    red: '31',
    green: '32',
    yellow: '33',
    blue: '34'
};
/**
 * @param {string} text
 * @param {keyof typeof colorSet | "default"} [color='default']
 * @param {"log" | "error"} [consoleType]
 */
function coloredStd(text, color = 'default', consoleType = 'log') {
    /** @type {Array<keyof typeof colorSet>} */
    const colors = /** @type {Array<keyof typeof colorSet>} */ (
        Object.keys(colorSet)
    );
    if (consoleType !== 'log' && consoleType !== 'error') consoleType = 'log';
    if (color !== 'default' && !colors.includes(/** @type {any} */ (color)))
        color = 'default';
    if (color === 'default') {
        console[consoleType](text);
    } else {
        console[consoleType](colorizeText(text, color));
    }
}
/**
 * @param {string} text
 * @param {keyof typeof colorSet | "default"} color
 */
function coloredLog(text, color = 'default') {
    coloredStd(text, color, 'log');
}
/**
 * @param {string} text
 */
function coloredError(text) {
    coloredStd(text, 'red', 'error');
}
/**
 * @param {string} text
 * @param {keyof typeof colorSet} color
 */
function colorizeText(text, color) {
    /** @type {Array<keyof typeof colorSet>} */
    const colors = /** @type {Array<keyof typeof colorSet>} */ (
        Object.keys(colorSet)
    );
    if (!colors.includes(color)) return text;
    return `\x1b[${colorSet[color]}m ${text} \x1b[0m`;
}
/**
 *
 *
 * @param {string} version
 * @param {string} mainPluginFile
 * @param {string} mainPluginFilePath
 * @param {string} outPathTemp
 */
async function replaceVersioInFiles(
    version,
    mainPluginFile,
    mainPluginFilePath,
    outPathTemp
) {
    verifyVersion(version); // This throws an error if the version is invalid
    try {
        const commentLine = ' * Version: x.x.x';
        const constantDeclaration =
            "// define('WPR_DIAGNOSER_VERSION', 'x.x.x');";
        const content = await fs.readFile(mainPluginFilePath, {
            encoding: 'utf-8'
        });
        if (
            !content.includes(commentLine) ||
            !content.includes(constantDeclaration)
        ) {
            await removeOutPathTemp(outPathTemp);
            console.log('');
            coloredError(
                'Could not set the version of the plugin. Lines changed? Please check\n'
            );
            process.exit(1);
        }
        let replaced = content.replace(
            ' * Version: x.x.x',
            ` * Version: ${version}`
        );
        replaced = replaced.replace(
            constantDeclaration,
            `define('WPR_DIAGNOSER_VERSION', '${version}');`
        );
        await fs.rm(mainPluginFile, { force: true });
        await fs.writeFile(mainPluginFilePath, replaced, { encoding: 'utf-8' });
    } catch (e) {
        console.error(
            colorizeText(
                `Something when wrong writing the version into the '${mainPluginFile}' file`,
                'red'
            ),
            e
        );
        process.exit(1);
    }
}
/**
 * @param {string} version
 * @return {true} Returns true if the version is valid
 * @throws {Error} Throws an error if the version is invalid
 */
function verifyVersion(version) {
    if (typeof version !== 'string')
        throw new Error(
            `The version must be 'string'. '${typeof version}' is invalid`
        );
    if (!version.match(/^\d\.\d\.\d$/)) {
        throw new Error(`The version is not valid. '${version}' is invalid`);
    }
    return true;
}
