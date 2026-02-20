import { useEffect } from "react"
import BannerGlobal from "../Componentes/BannerGlobal/BannerGlobal"
import NavBar from "../Componentes/NavBar/NavBar"
import Button from "../Componentes/Button/Button"

function NovaEdicao(){
    useEffect(()=>{
        document.title = "SGM - Nova Edição"
    },[])
    return(
        <>
            <BannerGlobal titulo="Nova Edição" voltar="/Home"/>
            <main className="flex flex-col items-center mt-10">
                <input className="w-[80%] shadow-md rounded-md p-2 mb-5" type="text" placeholder="Nome"/>
                <div className="w-[80%] shadow-md rounded-md p-5 text-center mb-5">
                    <img className="m-auto mb-2" src="/icons/upload-icon.svg" alt="Icone de Upload" />
                    <p className="text-gray-400">Arraste seu arquivo Excel/CSV aqui ou clique para selecionar</p>
                </div>
                <Button>
                    Continuar
                </Button>
            </main>
            <NavBar />
        </>
    )
}

export default NovaEdicao